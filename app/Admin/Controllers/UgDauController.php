<?php

namespace App\Admin\Controllers;

use App\Dauline;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Facades\DB;

class UgDauController extends Controller
{
    use HasResourceActions;
    protected $form;
    const CS = [
            'huawei'=>[
                'huawei_kr_video',
                'huawei_video',
                'huawei_mic_video' ,
            ],
            'xiaomi'=>[
                'xiaomi_video',
                'xm_video',
                'xm_content_video',
                'xm_home_video',
            ],
            'vivo'=>[
                'vivo_video'
            ],
            'meizu'=>[
                'meizu_video'
            ],
            'kukai'=>[
                'kukai_tv_video'
            ],
        ];

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $some = [];
        $cs = array_keys(self::CS);
        if(request()->getMethod() == 'POST'){
            $requestData = request()->toArray();
            if(empty($requestData['start']) || empty($requestData['end']) || !isset($requestData['cs'])){
                $content->withError('少参数');return;
            }
            $inputSrcIndex =$requestData['cs'];
            $inputSrcStr = $cs[$inputSrcIndex];
            $inputSrc = self::CS[$inputSrcStr];
            $sdate = $requestData['start'];
            $edate = $requestData['end'];
            DB::enableQueryLog();
            $data = DB::table('tt_dau_line')->select('createAt','src', DB::raw('sum(nums) as total') )
                ->whereBetween('createAt', [$sdate, $edate] )
                ->whereIn('src', $inputSrc)
                ->groupBy('createAt', 'src')
                ->orderBy('createAt')
                ->get()->map(function($v){return (array)$v;})->toArray();
            $series=[];
            $formatData=[];
            if(empty($data)){
                $content->withWarning($inputSrcStr. ' no dau data');
            }
            //var_dump(DB::getQueryLog());
            foreach ($data as $v){
                $formatData[$v['src']][$v['createAt']] = $v['total'];
            }
            for($sdateStamp = strtotime($sdate), $edateStamp = strtotime($edate);$sdateStamp<=$edateStamp;$sdateStamp+=86400) {
                $some[] = date('Y-m-d', $sdateStamp);
                foreach ($formatData as $key=>$v){
                    $series[$key]['name'] = $key;
                    $series[$key]['type']= 'line';
                    $series[$key]['stack']= '总量';
                    $date = date('Y-m-d', $sdateStamp);
                    if(array_key_exists($date, $v)){
                        $series[$key]['data'][] = $v[$date];
                    }else{
                        $series[$key]['data'][] = 0;
                    }
                }
            }
            $series = array_values($series);
            //输出对齐
            foreach ($series as $v){
                $cs[]=$v['name'];
            }

            return $content
                ->header('Chartjs')
                ->body($this->form())
                ->body(new Box('图表',
                        view('ugvideo.index', [
                            'some'=>\GuzzleHttp\json_encode($some),
                            'cs'=>\GuzzleHttp\json_encode($cs),
                            'series'=>\GuzzleHttp\json_encode($series),
                        ]))
                );
        }else{
            return $content
                ->header('Chartjs')
                ->body($this->form())
                ->body(new Box('图表',
                        view('ugvideo.index', [
                            'some'=>\GuzzleHttp\json_encode($some),
                            'cs'=>\GuzzleHttp\json_encode($cs),
                            'series'=>\GuzzleHttp\json_encode([]),
                        ]))
                );
        }



    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Dauline);



        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Dauline::findOrFail($id));



        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {

        $form = new Form(new Dauline);
        $form->dateRange('start', 'end', '时间');
        $cs = array_keys(self::CS);
        $form->select('cs', '厂商')->options($cs)->value($cs)->default($cs[0]);
        $form->tools(function (Form\Tools $tools){
            $tools->disableList();
            $tools->disableDelete();
            $tools->disableView();

        });

        /*$form->saving(function (Form $form) {
            var_dump($form->input(null));
            die;
        });*/
        $form->setAction('dauline');
        return $form;
    }

}
