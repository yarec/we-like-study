create or replace procedure init_module_user is
/**
  �û�ģ������ݱ��ʼ��
  �����ı���: �û�,Ȩ��,�û���,�û���-Ȩ��-��Զ�,�û�-�û���-��Զ�,session��
  �û���Ȩ��,��ͨ���û������жϵ�
  session�����ڴ��,ϵͳ�ر�ʱ�Զ��ر�,ϵͳ����ʱ����
 
  ��ҵ���,�û���ص�������:
  1 �����Ա��(�����û���,����Ȩ��)
  2 Ա����ְ  (�޸�״ֵ̬)
  3 �û�
 
  ��ҵ���,�û����ܹ��У�
  1 ��Ӧ��,ӵ�� ����״̬��ѯ
  2 ��ά��,ӵ������Ȩ��
  3 �����쵼,���� ��� ��ע 
  4 ����Ա��,�����Ƶ� �����ύ �����ظ� �������� (���� �����ظ��ص�)
  
  (SCM��,������Ҫ�� 
       ����:˵������Ҫʲô
       ����:  ˵�����Ǵ�������Щ��Ӧ����Щʲô
       ��浥:˵���ֿ��д���Щʲô) 
       
  (һ����ҵ��ϵͳ�в�����ҵ������,����Ҫ�е��ֶ�:
       ����ʱ�� �����ݿ������ɵ�ʱ��
       ��Чʱ�� ��ҵ�����ݱ�ϵͳ��ʽ���õ�ʱ��
       ʧЧʱ�� ��ҵ�����ݱ�ϵͳͣ�õ�ʱ��
       ����״̬ ������ҵ����������״̬,��: ���ڴ�����,�ȴ�����ͨ��(�Լ��������ĸ�����),����,ͣ������(�Լ���������),ͣ��
       ����     �������ҵ����������ת�����в������κ���Ҫ��ϸ˵��������
       )
 **/
flag NUMBER;
begin
      flag := 0;
      --�ж��û����Ƿ����,������ɾ���ؽ�
      select count(*) into flag from all_tables where table_name=upper('member');
      if (flag<>0) then
         execute immediate 'drop table MEMBER';
      end if;
      execute immediate '
      create table member (
              keyid        int PRIMARY KEY
             ,username     varchar(200)
             ,password     varchar(200)
             ,remark       varchar(400)
             )
      ' ;
      
      --�ж�Ȩ�ޱ��Ƿ����,������ɾ���ؽ�
      select count(*) into flag from all_tables where table_name=upper('permission');
      if (flag<>0) then
         execute immediate 'drop table permission';
      end if;
      execute immediate '
      create table permission (
              keyid        int PRIMARY KEY
             ,name         varchar(200)
             ,remark       varchar(400)
             )
      ' ;
      
      --�ж�Ȩ�ޱ��Ƿ����,������ɾ���ؽ�
      select count(*) into flag from all_tables where table_name=upper('people');
      if (flag<>0) then
         execute immediate 'drop table people';
      end if;
      execute immediate '
      create table people (
              keyid        int PRIMARY KEY
             ,realname     varchar(200)
             ,sex          int
             ,birthday     date
             ,degree       int
             ,marrage      int
             ,nationality  varchar(2)
             ,stock        int
             ,remark       varchar(400)
             )
      ' ;      
      
      execute immediate 'PURGE RECYCLEBIN';
end init_module_user;
/
