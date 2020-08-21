<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\Notifiable;

/**
 * @method static select(array $lookup_columns)
 * @method static whereIn(string $lookup_column, array $lookup_data)
 * @method static whereDate(string $lookup_column, string $operator, false|string $lookup_date)
 * @method static join(string $parent_table, string $parent_table_column, string $child_table_column)*@method static select(string$string, string$string1, string$string2, string$string3, string$string4, string$string5, string$string6, string$string7, string$string8)
 */
class Reservation extends Model
{
    use Notifiable;

    private const _STD_KOR_RES_INDEX_SUCCESS = "예약한 스케줄 조회에 성공하였습니다. ";
    private const _STD_KOR_RES_INDEX_NO_DATE = "예약된 스케줄이 없습니다.";

    /* 기본키 설정 */
    protected $primaryKey = 'res_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    // 한명 또는 여러명의 학생을 조회 가능
    public function get_std_kor_res(
        array $std_kor_id
    ): object
    {
        $settings = new Setting();
        $date_sch_res_possible = $settings->get_res_possible_date();

        return
            self::join('schedules', 'reservations.res_sch', 'schedules.sch_id')
                ->whereDate('sch_start_date', '>=', $date_sch_res_possible['from'])
                ->whereDate("sch_start_date", "<", $date_sch_res_possible['to'])
                ->whereIn('res_std_kor', $std_kor_id)
                ->orderBy('res_std_kor')->get();
    }

    /**
     * @param int $std_kor_id
     * @param string $search_date
     * @return JsonResponse
     */
    public function get_std_kor_res_by_date(
        int $std_kor_id,
//        string $std_kor_mail,
        string $search_date
    ): JsonResponse
    {
        $lookup_columns = [
            'std_for_lang', 'std_for_name',
            'sch_start_date', 'sch_end_date',
            'res_state_of_permission', 'res_state_of_attendance',
            'sch_state_of_result_input', 'sch_state_of_permission',
            'sch_for_zoom_pw'
        ];

        $result =
            self::select($lookup_columns)
                ->join('schedules as sch', 'sch.sch_id', '=', 'reservations.res_sch')
                ->join('student_foreigners as for', 'for.std_for_id', '=', 'sch.sch_std_for')
                ->where('reservations.res_std_kor', $std_kor_id)
                ->whereDate('sch.sch_start_date', $search_date)
                ->get();

        $is_std_kor_res_no_date = $result->count();

        if (!$is_std_kor_res_no_date) {
            return response()->json([
                'message' => self::_STD_KOR_RES_INDEX_NO_DATE
            ], 205);
        }

        $message_template = self::_STD_KOR_RES_INDEX_SUCCESS;
        return response()->json([
            'message' => $message_template,
            'data' => $result
        ], 200);
    }
}
