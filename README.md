# thinkPhp3.2 关联查询 1对1  封装类
实现方式：
$linkq=new LinkqController();

$linkq->tableName='goods';

$linkq->where=['id'=>15]; 

$linkq->field='id,title';

$linkq->hasOne("type_id",'goods_type',"title","type_name");

$linkq->hasOne('alb_id','alb','alb_name','alb_name');

dump($linkq->findAll());
