<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2018/1/31
 * Time: 19:55
 */

namespace App\Services;
use App\Models\Grade;


/**
 * 用户等级相关
 * Class UserGradeService
 * @package App\Services
 */
class UserGradeService
{
    /**
     * 获取所有等级信息
     * @return array
     */
    public function grades(){
        $gradeModels = Grade::orderBy("sort", "desc")->get()->toArray();
        $grades = [];
        foreach ($gradeModels as $gradeModel){
            $grades[$gradeModel['grade']] = $gradeModel;
        }
        return $grades;
    }


    /**
     * 获取等级信息
     * @param $grade
     * @return mixed
     */
    public function getGrade($grade){
        $grades = $this->grades();
        return $grades[$grade];
    }
}