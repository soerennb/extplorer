rem ----------------------------------------------------------------------------
rem
rem Component Install Archive Builder
rem
rem This file is part of eXtplorer
rem
rem ----------------------------------------------------------------------------

rem YOU MUST HAVE INSTALLED THE 4.x VERSION OF 7zip
rem Please update the program path here accordingly


set PATH="c:\Projekte\extplorer\git-extplorer\"
set ZIPPATH="C:\Programme\7-Zip"

cd %PATH%

%ZIPPATH%\7z.exe a -ttar -r %PATH%\scripts.tar scripts
%ZIPPATH%\7z.exe d -r %PATH%\scripts.tar .svn\
%ZIPPATH%\7z.exe a -tgzip %PATH%\scripts.tar.gz %PATH%\scripts.tar
del %PATH%\scripts.tar

%ZIPPATH%\7z.exe a -tzip -r %PATH%\com_extplorer.zip
%ZIPPATH%\7z.exe d -r %PATH%\com_extplorer.zip .svn\ .git\
%ZIPPATH%\7z.exe d %PATH%\com_extplorer.zip scripts\ archive\
%ZIPPATH%\7z.exe d -r %PATH%\com_extplorer.zip build_component.sh build_component.bat .project .projectOptions .cache .gitignore

del %PATH%\scripts.tar.gz
