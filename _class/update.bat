@chcp 1251
@SET class=%1
@SET field=%2
@echo class:%class% (%1) field:%field% (%2)
@if "%class%" == "" (
@  SET /p class=Задайте ID класса:
)
@if "%field%" == "" (
@  SET /p field=Задайте имя поля:
)
@if "%class%" == "" exit
@if "%field%" == "" exit
@echo class:%class% field:%field% продолжаем разговор...
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
@echo Полный запрос в %class%%field%.sql
@D:\work\OpenServer\modules\database\MySQL-5.5\bin\mysql -u root -proot russianknife_kn < %class%%field%.sql
@echo код завершения mysql:%errorlevel%
