<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Promo;
use App\Models\PromoBlackout;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

/**
 * Плашка «Сейчас действует»: какие акции идут в эту минуту по времени Симферополя.
 * Сервер рисует её сразу, а public/assets/js/promos-now.js раз в минуту пересчитывает по тем же правилам —
 * если гость держит страницу открытой, плашка сама сменится в 12:00 и в 15:00.
 *
 * Перенесено из старого сайта (inc/_promos.php). Логика PHP и JS обязана совпадать: общие случаи —
 * в tests/fixtures/promos-cases.json, их гоняют и Pest, и node --test.
 */
class PromoSchedule
{
    public const TZ = 'Europe/Simferopol';

    private const WEEK = ['понедельник', 'вторник', 'среду', 'четверг', 'пятницу', 'субботу', 'воскресенье'];

    /**
     * Правила из базы в том же виде, что уходит скрипту в data-promos.
     *
     * @return array{tz: string, holidays: list<string>, no_dates: list<string>, promos: list<array{id: string, title: string, short: ?string, when: array<string, mixed>}>}
     */
    public function rules(): array
    {
        $promos = Promo::where('is_active', true)->orderBy('position')->get()
            // Закончившиеся акции скрипту не нужны: он смотрит на неделю вперёд
            ->filter(fn (Promo $promo) => $promo->hasSchedule()
                && ($promo->valid_to === null || $promo->valid_to->format('Y-m-d') >= CarbonImmutable::now(self::TZ)->format('Y-m-d')))
            ->map(function (Promo $promo): array {
                // Ключи — как в старом data/promos.php: пустые не пишем, скрипт проверяет их наличие
                $when = ['days' => array_map('intval', $promo->days ?? [])];
                if ($promo->time_from && $promo->time_to) {
                    $when['from'] = $promo->time_from;
                    $when['to'] = $promo->time_to;
                }
                if ($promo->not_holidays) {
                    $when['not_holidays'] = true;
                }
                // Период действия акции (необязательно): с какого и по какой день включительно
                if ($promo->valid_from) {
                    $when['since'] = $promo->valid_from->format('Y-m-d');
                }
                if ($promo->valid_to) {
                    $when['until'] = $promo->valid_to->format('Y-m-d');
                }

                return ['id' => $promo->slug, 'title' => $promo->title, 'short' => $promo->short, 'when' => $when];
            });

        return [
            'tz' => self::TZ,
            'holidays' => Holiday::orderBy('month_day')->pluck('month_day')->all(),
            'no_dates' => PromoBlackout::orderBy('date')->get()->map(fn (PromoBlackout $b) => $b->date->format('Y-m-d'))->all(),
            'promos' => $promos->values()->all(),
        ];
    }

    /**
     * Акции, которые сегодня или в ближайшие дни можно показать в плашке, с подписью «когда».
     *
     * @param  array{holidays: list<string>, no_dates: list<string>, promos: list<array{id: string, title: string, short: ?string, when: array<string, mixed>}>}  $rules
     * @return list<array{id: string, title: string, short: ?string, kind: string, label: string}>
     */
    public function items(array $rules, ?DateTimeImmutable $now = null): array
    {
        // Через Carbon, а не new DateTimeImmutable: так время можно подменить в тестах и в симуляторе админки
        $now ??= CarbonImmutable::now(self::TZ);
        $minutes = (int) $now->format('G') * 60 + (int) $now->format('i');

        // Сегодня: идёт сейчас или начнётся позже; затем — ближайший день, когда что-то будет
        for ($offset = 0; $offset <= 7; $offset++) {
            $day = $now->modify('+'.$offset.' day');
            $found = [];
            foreach ($rules['promos'] as $promo) {
                $when = $promo['when'];
                if (! $this->runsOn($when, $day, $rules)) {
                    continue;
                }
                $from = isset($when['from']) ? $this->minutes($when['from']) : null;
                $to = isset($when['to']) ? $this->minutes($when['to']) : null;
                if ($offset === 0) {
                    if ($from === null) {
                        $found[] = $this->item($promo, 'live', 'Сегодня весь день');
                    } elseif ($minutes >= $from && $minutes < $to) {
                        $found[] = $this->item($promo, 'live', 'Сейчас, до '.$when['to']);
                    } elseif ($minutes < $from) {
                        $found[] = $this->item($promo, 'today', 'Сегодня с '.$when['from']);
                    }
                } else {
                    $weekday = (int) $day->format('N');
                    $name = $offset === 1 ? 'Завтра' : ($weekday === 2 ? 'Во вторник' : 'В '.self::WEEK[$weekday - 1]);
                    $found[] = $this->item($promo, 'soon', $name.($from === null ? '' : ' с '.$when['from']));
                }
            }
            // Сегодня что-то есть — показываем только сегодняшнее; нет — первый день, когда будет
            if ($found) {
                // Сначала то, что идёт сейчас (сортировка устойчивая — порядок акций сохраняется)
                usort($found, fn (array $a, array $b): int => ($a['kind'] !== 'live') <=> ($b['kind'] !== 'live'));

                return $found;
            }
        }

        return [];
    }

    /**
     * Идёт ли акция в этот день недели и не выпадает ли день на праздник или концерт.
     *
     * @param  array<string, mixed>  $when
     * @param  array{holidays: list<string>, no_dates: list<string>}  $rules
     */
    private function runsOn(array $when, DateTimeImmutable $day, array $rules): bool
    {
        if (! in_array((int) $day->format('N'), $when['days'], true)) {
            return false;
        }
        // Даты в виде 2026-10-05 сравниваются как строки
        $ymd = $day->format('Y-m-d');
        if ((isset($when['since']) && $ymd < $when['since']) || (isset($when['until']) && $ymd > $when['until'])) {
            return false;
        }

        return empty($when['not_holidays'])
            || ! (in_array($day->format('m-d'), $rules['holidays'], true) || in_array($day->format('Y-m-d'), $rules['no_dates'], true));
    }

    private function minutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }

    /**
     * @param  array{id: string, title: string, short: ?string}  $promo
     * @return array{id: string, title: string, short: ?string, kind: string, label: string}
     */
    private function item(array $promo, string $kind, string $label): array
    {
        return ['id' => $promo['id'], 'title' => $promo['title'], 'short' => $promo['short'], 'kind' => $kind, 'label' => $label];
    }
}
