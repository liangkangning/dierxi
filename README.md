# thinkPhp3.2 关联查询 1对1 1对多  封装类
实现方式：
        $linkq=new LinkqController();
        
        $linkq->tableName='device';
        
        $linkq->where=['id'=>1];
        
        $linkq->field='id,title,macno,address';
        
        $linkq->hasOne("device_type_id",'device_type',"title,remark",['title'=>'d_ty_title','remark'=>'d_t_remark']);
        
        $linkq->hasOne('agent_id','agent','agent_name');
        
        $linkq->hasOne('staff_id','staff','name,phone',['name'=>'staff_name','phone'=>'staff_phone']);
        
        $linkq->hasMany('id','order_sell','device_id','sum(real_money) as rm','order_sell','sum');



