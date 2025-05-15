<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TongjiModel extends Model
{
    protected $table = 'tongji';

    protected TongjiMdModel $md;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->md = new TongjiMdModel();
    }

    /**
     * 缓存key.
     * @return string
     */
    public function getCacheKey()
    {
        return 'xnadmin_tongji_' . md5(root_path());
    }

    /**
     * K线图 每小时数据
     * 获取某日每个时间段数据.
     * @param mixed $date
     * @param mixed $title
     * @return array
     */
    public function _getHourData($date, $title = '')
    {
        // PV数据
        $pv_data = $this->field("count(id) as number,FROM_UNIXTIME(create_time, '%H' ) as `hour`")
            ->whereDay('create_time', $date)
            ->group('hour')
            ->select()->toArray();

        // IP数据
        $ip_data = $this->field("count(DISTINCT ip) as number,FROM_UNIXTIME(create_time, '%H' ) as `hour`")
            ->whereDay('create_time', $date)
            ->group('hour')
            ->select()->toArray();
        $new_ip_data = $this->field("count(DISTINCT ip) as number,FROM_UNIXTIME(create_time, '%H' ) as `hour`")
            ->where('is_new', 1)
            ->whereDay('create_time', $date)
            ->group('hour')
            ->select()->toArray();

        $_pv_data = [];
        foreach ($pv_data as $v) {
            $_pv_data[$v['hour']] = $v['number'];
        }
        $_ip_data = [];
        foreach ($ip_data as $v) {
            $_ip_data[$v['hour']] = $v['number'];
        }
        $_new_ip_data = [];
        foreach ($new_ip_data as $v) {
            $_new_ip_data[$v['hour']] = $v['number'];
        }

        $hours        = [];
        $pv_datas     = [];
        $ip_datas     = [];
        $new_ip_datas = [];
        if ($date == date('Y-m-d')) {
            $H = date('H');
        } else {
            $H = 23;
        }
        for ($i = 0; $i <= $H; ++$i) {
            $hour           = str_pad($i, 2, '0', STR_PAD_LEFT);
            $hours[]        = $hour . ':00';
            $pv_datas[]     = $_pv_data[$hour]     ?? 0;
            $ip_datas[]     = $_ip_data[$hour]     ?? 0;
            $new_ip_datas[] = $_new_ip_data[$hour] ?? 0;
        }

        return [
            'title' => $title,
            'days'  => $hours,
            'item'  => [
                [
                    'title'  => '浏览量(PV)',
                    'number' => $pv_datas,
                ],
                [
                    'title'  => 'IP数',
                    'number' => $ip_datas,
                ],
                [
                    'title'  => '新IP数',
                    'number' => $new_ip_datas,
                ],
            ],
        ];
    }

    /**
     * K线图 获取近N天数据.
     * @param mixed $start_date
     * @param mixed $end_date
     * @param mixed $title
     */
    public function _getDayData($start_date, $end_date, $title = '')
    {
        // 开始日期到结束日期的天数
        $dataList = $this->md->field('pv,ip,new_ip,`date`')
            ->where('type', 1) // 日
            ->whereBetweenTime('date', $start_date, $end_date)
            ->order('date asc')
            ->select()->toArray();

        $days_list = $this->xn_date_list($start_date, $end_date);
        $days      = [];
        foreach ($days_list as $val) {
            $days[] = date('m/d', strtotime($val));
            foreach ($dataList as $v) {
                if ($v['date'] == $val) {
                    $pv     = $v['pv'];
                    $ip     = $v['ip'];
                    $new_ip = $v['new_ip'];
                    break;
                }
                $pv     = 0;
                $ip     = 0;
                $new_ip = 0;
            }
            $pv_datas[]     = $pv;
            $ip_datas[]     = $ip;
            $new_ip_datas[] = $new_ip;
        }

        return [
            'title' => $title,
            'days'  => $days,
            'item'  => [
                [
                    'title'  => '浏览量(PV)',
                    'number' => $pv_datas,
                ],
                [
                    'title'  => 'IP数',
                    'number' => $ip_datas,
                ],
                [
                    'title'  => '新IP数',
                    'number' => $new_ip_datas,
                ],
            ],
        ];
    }

    /**
     * K线图 获取近N月数据.
     * @param mixed $date
     * @param mixed $title
     */
    public function _getMonthData($date, $title = '')
    {
        $datas = $this->md->field('pv,ip,new_ip,`date`')
            ->where('type', 2)
            ->where('date', '>=', $date)
            // ->limit($month)
            ->order('date asc')
            ->select()->toArray();

        $days         = [];
        $pv_datas     = [];
        $ip_datas     = [];
        $new_ip_datas = [];
        foreach ($datas as $v) {
            $days[]         = date('m月', strtotime($v['date']));
            $pv_datas[]     = $v['pv'];
            $ip_datas[]     = $v['ip'];
            $new_ip_datas[] = $v['new_ip'];
        }
        return [
            'title' => $title,
            'days'  => $days,
            'item'  => [
                [
                    'title'  => '浏览量(PV)',
                    'number' => $pv_datas,
                ],
                [
                    'title'  => 'IP数',
                    'number' => $ip_datas,
                ],
                [
                    'title'  => '新IP数',
                    'number' => $new_ip_datas,
                ],
            ],
        ];
    }

    // 来源引擎
    public function _getEngine($date, $title = '')
    {
        $model = $this;
        if ($date == date('Y-m-d')) {
            $model = $model->whereDay('create_time');
        } else {
            $start_date = $date;
            $end_date   = date('Y-m-d') . ' 23:59:59';
            $model      = $model->whereBetweenTime('create_time', $start_date, $end_date);
        }

        $datas = $model->field('count(DISTINCT ip) as number,engine')
            ->where('engine', '<>', '站内')
            ->group('engine')
            ->order('number desc')
            ->select()->toArray();

        $legendData = [];
        $seriesData = [];
        foreach ($datas as $v) {
            $legendData[] = $v['engine'];
            $seriesData[] = ['value' => $v['number'], 'name' => $v['engine']];
        }
        return [
            'title'      => $title,
            'name'       => '来源引擎',
            'legendData' => $legendData,
            'seriesData' => $seriesData,
        ];
    }

    /**
     * 获取一段时间内的所有日期
     * @param $begdate 2015-05-05
     * @param $enddate 2015-05-10
     * @return array
     */
    public function xn_date_list($begdate, $enddate)
    {
        $begdate = strtotime($begdate);
        $enddate = strtotime($enddate);
        $tmp     = [];
        for ($date = $begdate; $date <= $enddate; $date += 86400) {
            $tmp[] = date('Y-m-d', $date);
        }
        return $tmp;
    }
}
