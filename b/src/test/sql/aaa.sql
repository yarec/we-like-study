--��֯������Ȩ�����
/*��ջ���վ  
drop table nst_person
PURGE RECYCLEBIN 
*/

/*��֯������ÿ����˾���е�,�Ǽܹ�ҵ��ϵͳ�Ļ���
��֯��������,����������һ����׼������ÿ������ҵ��λ������һ�������,Ψһ�ı��
*/
create table nst_organization (
        guid      varchar(200)        primary key --����,ʹ�� SYS-ID,���Բ���һ��ȫ��Ψһ�ı��,��֪�����ݿ��ܷ����
       ,key       varchar(200)        unique      --��֯��������,�в㼶��ϵ�ı���,����������νṹ
       ,name      varchar(200)        not null    --��֯��������
       ,remark    varchar2(4000)                  --��֯��������,����һ�����,����Ҫ�洢 HTML �ַ���
)

/*��Ա��,����������ʵ�����д��ڵĸ��˵�λ
*/
create table nst_person(
        guid     varchar(200)         primary key --����,ʹ�� SYS-ID
       ,name     varchar(200)                     --����,��Щϵͳ������� first-name , last-name ��¼����������,���Դ���
       ,gender   int                           --�ο������ļ����Ա�Ķ���: 0 δ֪,1��,2Ů,9�����Ա�
       ,birthday date                             --����
       ,birthplace     varchar(200)               --������
       ,nationality    varchar(200)               --����
       ,nation   varchar(200)                     --����
       ,degree   varchar(200)                     --ѧ��
       ,photo    varchar(300)                     --��Ƭ URL ·��
       ,height   int                              --���
       ,phone    varchar(200)                     --�绰��ϵ��ʽ
)

/*��׼���ݿ�,�����洢������׼����,����������������,�䶯��С������
*/
create table nst_standards (
     code             varchar(200) not null
    ,value            varchar(200) not null
    ,source           varchar(200) not null
    ,remark           varchar2(400) 
    
    ,txt1             varchar(200) 
    ,txt2             varchar(200) 
    ,txt3             varchar(200) 
    ,txt4             varchar(200) 
    
)
comment on table  nst_standards is                       '��׼';       
comment on column nst_standards.code is                  '����';
comment on column nst_standards.value is                 'ֵ';
comment on column nst_standards.source is                '��Դ';
comment on column nst_standards.remark is                '��ע';
      

      
PURGE RECYCLEBIN