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
     public $order;
     public $field=null;
     protected $linkqTabel=array();
     protected $linkqTabelHasMany=array();
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
         $a=$query->order($this->order)->select();
         //end 查询主表的数据

         if (count($this->linkqTabel)>0||count($this->linkqTabelHasMany)>0){

                 //一对一的循环匹配
                 foreach ($this->linkqTabel as $key=>$value){
                     $valeu_array=$this->getColumn($a,$value['value']);//获取主表要查询的值
                     $list=M($value['table'])->where(['id'=>['in',$valeu_array]])->Field('id,' . $value['field'])->select();
                     $list=$this->array_column($list, NULL, 'id');//以id作为key
                     $a=$this->mergeArrays($a,$value['value'],$list,$value['table'],$value['new_field']);
                 }

                 //一对多的循环匹配
                foreach ($this->linkqTabelHasMany as $key=>$value){
                    $valeu_array=$this->getColumn($a,$value['value']);//获取主表要查询的值
                    if ($value['action']=='list'){
                     $list=M($value['table'])->where([$value['table_value']=>['in',$valeu_array]])->Field('id,' . $value['field'] . ',' . $value['table_value'])->select();

                    }

                    if ($value['action']=='sum' || $value['action']=='count'){
                        $list=M($value['table'])->where([$value['table_value']=>['in',$valeu_array]])->Field('id,' . $value['field'] . ',' . $value['table_value'])->group($value['table_value'])->select();
//                        dump($list);
                    }

                    $list=self::array_group_by($list,$value['table_value']);
                    $a=$this->mergeArrays($a,$value['value'],$list,$value['new_value'],'',$value['action']);


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
     public function order($data){
          $this->order=$data;
     }
    public function field($data){
        $this->field=$data;
    }
    public function where($data){
        $this->where=$data;
    }
     public function hasOne($value,$table,$field,$new_field=null){
         $a=array('value'=>$value,'table'=>$table,'field'=>$field,'new_field'=>$new_field);
         array_push($this->linkqTabel,$a);
         $this->is_diff_field($this->field,$value)?array_push($this->diff_field,$value):'';//field没有关联的字段
     }

     public function hasMany($value,$table,$table_value,$field,$new_value,$action='list'){
         $a=array('value'=>$value,'table'=>$table,'field'=>$field,'table_value'=>$table_value,'new_value'=>$new_value,'action'=>$action);
         array_push($this->linkqTabelHasMany,$a);
         $this->is_diff_field($this->field,$value)?array_push($this->diff_field,$value):'';//field没有关联的字段

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


     public function mergeArrays($arr//主数组
         ,$key//数组某个key
         ,$other_arr//合并的另外一个数组
         ,$table_name//table_name 新数字的key
         ,$new_field//或者根据field 来新的key
         ,$action='list'
     ){
         $new_a=array();

         foreach ($arr as $k=>$v){
             if (is_null($new_field) || empty($new_field)){
                 $v[$table_name]=$other_arr[$v[$key]]?:null;
                 if ($action!='list' && !empty($other_arr[$v[$key]])){
                     $v[$table_name]=$other_arr[$v[$key]][0];
                 }
                 array_push($new_a,$v);
             }else{

                 foreach ($new_field as $kk=>$vv){
                     $v[$vv]=$other_arr[$v[$key]][$kk]?:null;
                 }
                 array_push($new_a,$v);

             }


         }
         return $new_a;

     }

    public static function array_group_by($arr, $key)
    {
        $grouped = [];
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
// Recursively build a nested grouping if more parameters are supplied
// Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $parms);
            }
        }
        return $grouped;
    }

//     获取2维数组中的key，形成数组
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
