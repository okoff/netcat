@chcp 1251
@SET class=%1
@SET field=%2
@echo class:%class% (%1) field:%field% (%2)
@if "%class%" == "" (
@  SET /p class=������� ID ������:
)
@if "%field%" == "" (
@  SET /p field=������� ��� ����:
)
@if "%class%" == "" exit
@if "%field%" == "" exit
@echo class:%class% field:%field% ���������� ��������...
@echo UPDATE Class SET %field%=^" > _pre1.sql
@rem @type _pre1.sql
@echo ^" where Class_ID=%class%; > _pre2.sql
@rem @type _pre2.sql
@copy /Y %class%%field%.html __field.sql > nul
@copy /Y __*.sql __*.sql.bak > nul
@fart.exe -q -B -b -- __*.sql "\\" "\\\\"
@fart.exe -q -B -b -- __*.sql """" "\\""
@fart.exe -q -B -b -- __*.sql "'" "\'"
@copy /Y _pre1.sql + __field.sql + _pre2.sql %class%%field%.sql > nul
@del _*.sql
@echo ������ ������ � %class%%field%.sql
@D:\work\OpenServer\modules\database\MySQL-5.5\bin\mysql -u root -proot russianknife_kn < %class%%field%.sql
@echo ��� ���������� mysql:%errorlevel%
