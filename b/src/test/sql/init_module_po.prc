create or replace procedure init_module_po is
flag NUMBER;
begin

/**
 ����ͷ
 
 ���漰��������������:
     ����,    �ڶ�������,�������������������
     �����,  ��������,������Щ���Ͻ��ᱻ�����ĸ��ط�,������ERP��������õ�
     ��Ŀ,    ����������
     ��Ӧ��,  ����ͷ��,������������µ����������Ǵ��ĸ���Ӧ��������
     ��ͬ,    ����ͷ����
 */
      flag := 0;
      select count(*) into flag from all_tables where table_name=upper('po_head');
      if (flag<>0) then
         execute immediate 'drop table po_head';
      end if;
      execute immediate ' 
      create table po_head (
           keyid            int PRIMARY KEY
          ,id_reference     int default 0 not null 
          ,po_no            varchar(200) not null
          ,price            number(16,6) default 0 not null       
          ,time_ordered     DATE default sysdate not null 
          ,time_created     DATE default sysdate not null 
          ,time_enable      DATE default to_date(''10000101'',''yyyymmdd'') not null 
          ,time_disable     DATE default to_date(''10000101'',''yyyymmdd'') not null           
          ,id_user_created  int not null 
          ,id_user_buyer    int not null 
          ,id_suppiler      int not null           
          ,id_contract      int not null  
          ,remark           varchar2(400) 
      )';
      execute immediate 'comment on table po_head is                        ''����ͷ'' '; 
      
      execute immediate 'comment on column po_head.po_no is                 ''������'' '; 
      execute immediate 'comment on column po_head.id_reference is          ''�ϼ����ݱ��.һ��������� ����,Ҳ�п�������ǰ��������'' ';
      execute immediate 'comment on column po_head.price is                 ''�����ܽ��'' '; 
      execute immediate 'comment on column po_head.time_ordered is          ''��������,��Ӧ���յ�������Ϣ��ʱ��'' '; 
      execute immediate 'comment on column po_head.time_created is          ''�Ƶ�����,���ݿ�������������¼��ʱ��'' '; 
      execute immediate 'comment on column po_head.time_enable is           ''����ʱ��,�����Ͳ�������״ֵ̬��,ֻҪ���ֵΪ1000-01-01��˵����δ���ͨ��'' '; 
      execute immediate 'comment on column po_head.time_disable is          ''����ʱ��,һ��������ʧ�ܵ�������,����ֱ������'' '; 
      execute immediate 'comment on column po_head.id_user_created is       ''�Ƶ���'' '; 
      execute immediate 'comment on column po_head.id_user_buyer is         ''�ɹ�Ա'' '; 
      execute immediate 'comment on column po_head.id_suppiler is           ''��Ӧ��'' '; 
      execute immediate 'comment on column po_head.id_contract is           ''��ͬ'' '; 
      execute immediate 'comment on column po_head.remark is                ''��ע'' ';
      

      
      flag := 0;
      select count(*) into flag from all_tables where table_name=upper('po_line');
      if (flag<>0) then
         execute immediate 'drop table po_line';
      end if;
      execute immediate ' 
        create table po_line (
             keyid            int PRIMARY KEY
            ,id_reference     int default 0 not null   
            ,id_po_head       int not null 
            ,id_material      int not null 
            ,id_project       int not null 
            ,id_task          int not null             
            ,count            int not null 
            ,price            number(16,6) default 0 not null 
            ,id_user_receiver int not null 
        )';
        
      execute immediate 'comment on table po_line is                        ''������'' '; 
      
      execute immediate 'comment on column po_line.id_reference is          ''�����е�ҵ����Դһ���� ���� ,������ǰ��������'' '; 
      execute immediate 'comment on column po_line.id_po_head is            ''����ͷ   '' '; 
      execute immediate 'comment on column po_line.id_material is           ''����'' '; 
      execute immediate 'comment on column po_line.id_project is            ''��Ŀ'' '; 
      execute immediate 'comment on column po_line.id_task is               ''�����'' '; 
      execute immediate 'comment on column po_line.count is                 ''��������'' '; 
      execute immediate 'comment on column po_line.price is                 ''����'' '; 
      execute immediate 'comment on column po_line.id_user_receiver is      ''������Ա'' '; 
      
      execute immediate 'PURGE RECYCLEBIN';
      
end init_module_po;
/
