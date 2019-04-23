<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/12 0012
 * Time: 下午 7:54
 */

namespace Common\Controller;

use Think\Controller;


class Lq extends Controller
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

    public function _initialize(){
        //初始化
        if(M('linkq')->count()==0){
            $this->addLinkq($this->getTables());
        }
        self::$p = I('p')||I('page')?I('p')+I('page'):1;
        self::$psize = I('pagesize')?:20;


    }

    public function getData($table){
//        self::check($table);
        $table=M('linkq')->where(['table_name'=>$table])->find();
        if (!$table){
            echo '没有该表的关联信息';
            return false;
        }else{
            if ($table['status']==0){
                echo '该表的关联错误提示：'.$table['message'];
                return false;
            }
        }

//        $tableData=M($table['table_name'])->where($where)->select();
        $query=M($table['table_name'])->where($this->where)->page(self::$p,self::$psize);

        if (!is_null($this->field)){
            $query->field($this->field);
        }
        $tableData=$query->order($this->order)->select();
        $json = json_decode($table['has_one'],true);
        foreach ($json as $key=>$value){
            foreach ($value as $k=>$v){
                $this->hasOne($k,$v);
            }
        }

//        dump($this->linkqTabel);
//
//        exit();
        if (count($tableData)>0){
            if (count($this->linkqTabel)>0||count($this->linkqTabelHasMany)>0){

                foreach ($this->linkqTabel as $key=>$value){

                    $valeu_array=self::getColumn($tableData,$value['value']);//获取主表要查询的值
                    $list=M($value['table'])->where(['id'=>['in',$valeu_array]])->select();
//                    dump($valeu_array);
                    $list=$this->array_column($list, NULL, 'id');//以id作为key
                    $tableData=$this->mergeArrays($tableData,$value['value'],$list,$value['table'],$value['new_field']);
                }
                //一对多的循环匹配
                foreach ($this->linkqTabelHasMany as $key=>$value){
                    $valeu_array=$this->getColumn($tableData,$value['value']);//获取主表要查询的值
                    if ($value['action']=='list'){
                        $list=M($value['table'])->where([$value['table_value']=>['in',$valeu_array]])->Field('id,' . $value['field'] . ',' . $value['table_value'])->select();

                    }

                    if ($value['action']=='sum' || $value['action']=='count'){
                        $list=M($value['table'])->where([$value['table_value']=>['in',$valeu_array]])->Field('id,' . $value['field'] . ',' . $value['table_value'])->group($value['table_value'])->select();
//                        dump($list);
                    }

                    $list=self::array_group_by($list,$value['table_value']);
                    $tableData=$this->mergeArrays($tableData,$value['value'],$list,$value['new_value'],'',$value['action']);


                }
            }

        }

        return $tableData;
//        dump($tableData);
//        exit();

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

    //获取所有的表名
    private function getTables()
    {
        //获取表名
        $table_list=[];
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $result = $Model->query('show tables');
        foreach ($result as $key=>$value){
            foreach ($value as $k=>$v){
                $query= 'SELECT COLUMN_NAME from INFORMATION_SCHEMA.Columns where TABLE_NAME='.'"'.$v.'"';
                $a=$Model->query($query);
                $b=[];
                foreach ($a as $kk=>$vv){
                    array_push($b,$vv['column_name']);

                }
                $table_list[$v]=$b;
            }
        }
        return $table_list;
    }

    private function addLinkq($data){
           foreach ($data as $key=>$value){
               $a=explode(C('DB_PREFIX'),$key);
               $table_name=$a[1];
               $value_list=[];
               foreach ($value as $k=>$v){
                   $bb= explode('_id',$v);
                   if (count($bb)>1){
                       if ($table_name!=$bb[0]){
                           $value_list[]=[$v=>$bb[0]];
                       }

                   }

               }
//               dump($value_list);exit();
               $this->add($table_name,$value_list);
//               exit();

           }

    }





    public function hasOne($value,$table){
        $a=array('value'=>$value,'table'=>$table);
        array_push($this->linkqTabel,$a);
    }
    public function hasMany($value,$table,$table_value,$field,$new_value,$action='list'){
        $a=array('value'=>$value,'table'=>$table,'field'=>$field,'table_value'=>$table_value,'new_value'=>$new_value,'action'=>$action);
        array_push($this->linkqTabelHasMany,$a);
        $this->is_diff_field($this->field,$value)?array_push($this->diff_field,$value):'';//field没有关联的字段

    }

    private function check($table){
        $res=M('linkq')->where(['table_name'=>$table])->find();
        if($res!==false){
           return true;
        }else{

            echo '该表未做关联';
        }

    }

    public function add($table_name,$value_list){
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
         $status=1;
         $message='';
            if (count($value_list)>0){
                foreach ($value_list as $k=>$v){
                    foreach ($v as$kkk){
                        $kkk=C('DB_PREFIX').$kkk;
                        $query= 'SHOW TABLES LIKE '.'"'.$kkk.'"';
                        $result = $Model->query($query);

                        if (count($result)==0){
//                            dump($result);
                            $status=0;
                            $message=$message.$kkk.'表不存在,';
                        }
                    }

                }
                $json = json_encode($value_list);
                $b=[];
                $b['table_name']=$table_name;
                $b['has_one']=$json;
                $b['status']=$status;
                $b['message']=$message;
                M('linkq')->Add($b);
//                dump($json);
            }



    }
    private function mergeArrays($arr//主数组
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

    //     获取2维数组中的key，形成数组
    static function getColumn($array,$key){
        $a=array();
        foreach ($array as $k=>$v){
            array_push($a,self::getValue($v,$key));
        }
        return $a;
    }


    //     获取数组中的某个值
    static  function getValue($array, $key, $default = null)
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

}
