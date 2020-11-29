@chcp 1251
@SET template=%1
@echo template:%template% (%1)
@if "%template%" == "" (
@  SET /p template=Задайте ID шаблона:
)
@if "%template%" == "" exit
@echo template:%template% продолжаем разговор...
@echo UPDATE Template SET Settings=^" > _pre1.sql
@rem @type _pre1.sql
@echo ^", Header=^" > _pre2.sql
@rem @type _pre2.sql
@echo ^", Footer= ^" > _pre3.sql
@rem @type _pre3.sql
@echo ^" where Template_ID=%template%; > _pre4.sql
@rem @type _pre4.sql
@copy /Y %template%settings.html __settings.sql > nul
@copy /Y %template%header.html __header.sql > nul
@copy /Y %template%footer.html __footer.sql > nul
@copy /Y __*.sql __*.sql.bak > nul
@fart.exe -q -B -b -- __*.sql "\\" "\\\\"
@fart.exe -q -B -b -- __*.sql """" "\\""
@fart.exe -q -B -b -- __*.sql "'" "\'"
@copy /Y _pre1.sql + __settings.sql + _pre2.sql + __header.sql + _pre3.sql + __footer.sql + _pre4.sql $query.sql > nul
@del _*.sql
@echo Полный запрос в $query.sql
@D:\work\OpenServer\modules\database\MySQL-5.5\bin\mysql -u root -proot russianknife_kn < $query.sql
@echo код завершения mysql:%errorlevel%
