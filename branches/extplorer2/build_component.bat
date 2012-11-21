rem ----------------------------------------------------------------------------
rem
rem Component Install Archive Builder
rem
rem This file is part of eXtplorer
rem
rem ----------------------------------------------------------------------------

rem YOU MUST HAVE INSTALLED THE 4.x VERSION OF 7zip
rem Please update the program path here accordingly


set PATH="c:\Projekte\extplorer\svn-extplorer2\"

cd %PATH%

C:\Programme\7-Zip\7z.exe a -ttar -r %PATH%\scripts.tar scripts
C:\Programme\7-Zip\7z.exe d -r %PATH%\scripts.tar .svn\
C:\Programme\7-Zip\7z.exe a -tgzip %PATH%\scripts.tar.gz %PATH%\scripts.tar
del %PATH%\scripts.tar

C:\Programme\7-Zip\7z.exe a -tzip -r %PATH%\com_extplorer.zip
C:\Programme\7-Zip\7z.exe d -r %PATH%\com_extplorer.zip .svn\
C:\Programme\7-Zip\7z.exe d %PATH%\com_extplorer.zip scripts\ archive\

C:\Programme\7-Zip\7z.exe d -r %PATH%\com_extplorer.zip build_component.sh build_component.bat .project .projectOptions .cache preinstall.php README_PREINSTALL.txt

del %PATH%\scripts.tar.gz
