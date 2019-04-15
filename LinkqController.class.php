<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/12 0012
 * Time: 下午 7:54
 */

namespace Common\Controller;

use Think\Controller;

class LinkqController extends Controller
{
     public $tableName;
     public $where;
     public $field=null;
     public $linkqTabel=array();
     protected $diff_field=array();
     static $p;//全局页码
     static $psize;//全局页数
      public function findAll(){
          self::$p = I('p')||I('page')?I('p')+I('page'):1;
          self::$psize = I('pagesize')?:20;
         $query=M($this->tableName)->where($this->where)->page(self::$p,self::$psize);
         if (!is_null($this->field)){
             $this->field=$this->addField($this->field,$this->diff_field);

             $query->field($this->field);
         }
         $a=$query->select();
//         var_dump($this->linkqTabel);
//         exit();
         if (count($this->linkqTabel)>0){

                 //每次查询需要优化
                 foreach ($this->linkqTabel as $key=>$value){
//                     var_dump($value);
                     $valeu_array=$this->getColumn($a,$value['value']);
                     $list=M($value['table'])->where(['id'=>['in',$valeu_array]])->Field('id,' . $value['table_value'])->select();
                     $list=$this->array_column($list, NULL, 'id');//以id作为key

//                     dump($list);
//                     exit();
                     $new_a=array();
                     foreach ($a as $k=>$v){
                         $v[$value['new_value']]=$list[$v[$value['value']]][$value['table_value']]?:null;
                         array_push($new_a,$v);
                     }
                     $a=$new_a;
                 }
                 $new_a=array();
                 foreach ($a as $key=>$value){
                    foreach ($this->diff_field as $k=>$v){
                        unset($value[$v]);
                    }
                     array_push($new_a,$value);
                 }
                 $a=$new_a;
         }
         return $a;
     }
     public function hasOne($value,$table,$table_value,$new_value){
         $a=array('value'=>$value,'table'=>$table,'table_value'=>$table_value,'new_value'=>$new_value);
         array_push($this->linkqTabel,$a);
         $this->is_diff_field($this->field,$value)?array_push($this->diff_field,$value):'';//field没有关联的字段
     }

     public function hasMany($value,$table,$table_value,$new_value,$sum=null){

     }



     //增加的查询字段，如果fied里面没有关联查询的字段，要添加进去
     private function addField($field,$diff_field){
//         var_dump($diff_field);
         $a=explode(',',$field);
         foreach ($diff_field as $v){
             array_push($a,$v);
         }
//         $a= array_unique($a);
         $a=implode(',',$a);
         return $a;
     }
     //判断关联查询时的字段，是否跟filed里面的字段重复
     private function is_diff_field($field,$value){
         $a=explode(',',$field);
         foreach ($a as $v){
             if ($v==$value){
                 return false;
             }
         }
         return true;
     }
//     获取数组中的某个值，形成数组
     private function getColumn($array,$key){
         $a=array();
         foreach ($array as $k=>$v){
             array_push($a,$this->getValue($v,$key));
         }
         return $a;
     }

    function array_column(array $array, $column_key, $index_key=null){
        $result = [];
        foreach($array as $arr) {
            if(!is_array($arr)) continue;

            if(is_null($column_key)){
                $value = $arr;
            }else{
                $value = $arr[$column_key];
            }

            if(!is_null($index_key)){
                $key = $arr[$index_key];
                $result[$key] = $value;
            }else{
                $result[] = $value;
            }
        }
        return $result;
    }


//     获取数组中的某个值
    private  function getValue($array, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array)) ) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== false) {
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            // this is expected to fail if the property does not exist, or __get() is not implemented
            // it is not reliably possible to check whether a property is accessable beforehand
            return $array->$key;
        } elseif (is_array($array)) {
            return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
        } else {
            return $default;
        }
    }


}