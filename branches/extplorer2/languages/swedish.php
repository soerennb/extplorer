<?php
// $Id$
// Swedish Language Module for v2.3 (translated by the Olle Zettergren)
global $_VERSION;

$GLOBALS["charset"] = "iso-8859-1";
$GLOBALS["text_dir"] = "ltr"; // ('ltr' for left to right, 'rtl' for right to left)
$GLOBALS["date_fmt"] = "Y-m-d H:i";
$GLOBALS["error_msg"] = array(
	// error
	"error"			=> "Fel",
	"message"		=> "Meddelande(n)",
	"back"			=> "Tillbaka",

	// root
	"home"			=> "Hemkatalogen finns inte, kontrollera dina inst�llningar.",
	"abovehome"		=> "Den aktuella katalogen kan inte vara ovanf�r hemkatalogen.",
	"targetabovehome"	=> "M�lkatalogen kan inte vara ovanf�r hemkatalogen.",

	// exist
	"direxist"		=> "Den h�r katalogen finns inte.",
	//"filedoesexist"	=> "This file already exists.",
	"fileexist"		=> "Den h�r filen finns inte.",
	"itemdoesexist"		=> "Det h�r objektet finns redan.",
	"itemexist"		=> "Det h�r objektet finns inte.",
	"targetexist"		=> "M�lkatalogen finns inte.",
	"targetdoesexist"	=> "M�lobjektet finns redan.",

	// open
	"opendir"		=> "Det g�r inte att �ppna katalogen.",
	"readdir"		=> "Det g�r inte att l�sa katalogen.",

	// access
	"accessdir"		=> "Du har inte tilltr�de till den h�r katalog.",
	"accessfile"	=> "Du har inte tilltr�de till den h�r filen.",
	"accessitem"	=> "Du har inte tilltr�de till det h�r objektet.",
	"accessfunc"	=> "Du har inte tilltr�de till den h�r funktionen.",
	"accesstarget"	=> "Du har inte tilltr�de till m�lkatalogen.",

	// actions
	"permread"		=> "Misslyckades med att l�sa filtillst�ndet.",
	"permchange"	=> "CHMOD-fel (Det h�r beror oftast p� ett problem med vem som �ger filen - t.ex. att HTTP-anv�ndaren �r ('wwwrun' eller 'nobody') och FTP-anv�ndaren inte �r densamma.)",
	"openfile"		=> "Gick inte att �ppna filen.",
	"savefile"		=> "Gick inte att spara filen.",
	"createfile"	=> "Gick inte att skapa filen.",
	"createdir"		=> "Gick inte att skapa katalogen.",
	"uploadfile"	=> "Gick inte att ladda upp filen.",
	"copyitem"		=> "Gick inte ta kopiera.",
	"moveitem"		=> "Gick inte att flytta.",
	"delitem"		=> "Gick inte att ta bort.",
	"chpass"		=> "Gick inte att byta l�senord.",
	"deluser"		=> "Gick inte att ta bort anv�ndare.",
	"adduser"		=> "Gick inte att l�gga till anv�ndare.",
	"saveuser"		=> "Gick inte att spara anv�ndare.",
	"searchnothing"	=> "Du m�ste ange n�gon att s�ka efter.",

	// misc
	"miscnofunc"		=> "Funktionen otillg�nglig.",
	"miscfilesize"		=> "Filen �verskrider maximalt till�ten storlek.",
	"miscfilepart"		=> "Filen laddades bara upp delvis.",
	"miscnoname"		=> "Du m�ste ange ett namn.",
	"miscselitems"		=> "Du har inte valt n�got/n�gra objekt.",
	"miscdelitems"		=> "�r du s�ker p� att du vill ta bort dessa {0} objekt?",
	"miscdeluser"		=> "�r du s�ker p� att du vill ta bort anv�ndare '{0}'?",
	"miscnopassdiff"	=> "Det nya l�senordet skiljer sig inte fr�n det aktuella.",
	"miscnopassmatch"	=> "L�senorden matchar inte.",
	"miscfieldmissed"	=> "Du missade ett viktigt f�lt.",
	"miscnouserpass"	=> "Anv�ndarnamn eller l�senord felaktiga.",
	"miscselfremove"	=> "Du kan inte ta bort dig sj�lv.",
	"miscuserexist"		=> "Anv�ndaren finns redan.",
	"miscnofinduser"	=> "Kan inte hitta anv�ndaren.",
	"extract_noarchive" => "Den h�r filen �r inte en uppackningsbar arkivfil.",
	"extract_unknowntype" => "Ok�nd akrivtyp",
	
	'chmod_none_not_allowed' => '�ndra r�ttigheter till <none> �r inte till�tet',
	'archive_dir_notexists' => 'Den katalog du har angivit att spara till finns inte.',
	'archive_dir_unwritable' => 'Ange en skrivbar katalog att spara arkivet till.',
	'archive_creation_failed' => 'Misslyckades att spara akrivfilen'
	
);
$GLOBALS["messages"] = array(
	// links
	"permlink"		=> "�ndra r�ttigheter",
	"editlink"		=> "Redigera",
	"downlink"		=> "Ladda ner",
	"uplink"		=> "Upp",
	"homelink"		=> "Hem",
	"reloadlink"	=> "Ladda om",
	"copylink"		=> "Kopiera",
	"movelink"		=> "Flytta",
	"dellink"		=> "Ta bort",
	"comprlink"		=> "Arkiv",
	"adminlink"		=> "Admin",
	"logoutlink"	=> "Logga ut",
	"uploadlink"	=> "Ladda upp",
	"searchlink"	=> "S�k",
	"extractlink"	=> "Packa upp akrivet",
	'chmodlink'		=> '�ndra (chmod) r�ttigheter (Mapp/Fil(er))', // new mic
	'mossysinfolink'	=> 'eXtplorer System Information (eXtplorer, Server, PHP, mySQL)', // new mic
	'logolink'		=> 'G� till siten f�r eXtplorer (�ppnas i nytt f�nster)', // new mic

	// list
	"nameheader"		=> "Namn",
	"sizeheader"		=> "Storlek",
	"typeheader"		=> "Type",
	"modifheader"		=> "�ndrad",
	"permheader"		=> "R�ttigheter",
	"actionheader"		=> "H�ndelser",
	"pathheader"		=> "S�kv�g",

	// buttons
	"btncancel"		=> "Avbryt",
	"btnsave"		=> "Spara",
	"btnchange"		=> "�ndra",
	"btnreset"		=> "�terst�ll",
	"btnclose"		=> "St�ng",
	"btncreate"		=> "Skapa",
	"btnsearch"		=> "S�k",
	"btnupload"		=> "Ladda upp",
	"btncopy"		=> "Kopiera",
	"btnmove"		=> "Flytta",
	"btnlogin"		=> "Logga in",
	"btnlogout"		=> "Logga ut",
	"btnadd"		=> "L�gg till",
	"btnedit"		=> "Redigera",
	"btnremove"		=> "Ta bort",
	
	// user messages, new in eXtplorer 1.3.0
	'renamelink'	=> 'Byt namn',
	'confirm_delete_file' => 'Are you sure you want to delete this file? <br />%s',
	'success_delete_file' => 'Item(s) successfully deleted.',
	'success_rename_file' => 'The directory/file %s was successfully renamed to %s.',
	
	// actions
	"actdir"			=> "Katalog",
	"actperms"			=> "�ndra r�ttigheter",
	"actedit"			=> "Redigera fil",
	"actsearchresults"	=> "S�kresultat",
	"actcopyitems"		=> "Kopiera objekt",
	"actcopyfrom"		=> "Kopiera fr�n /%s till /%s ",
	"actmoveitems"		=> "Flytta objekt",
	"actmovefrom"		=> "Flytta fr�n /%s till /%s ",
	"actlogin"			=> "Logga in",
	"actloginheader"	=> "Logga in f�r att anv�nda eXtplorer",
	"actadmin"			=> "Administration",
	"actchpwd"			=> "�ndra l�senord",
	"actusers"			=> "Anv�ndare",
	"actarchive"		=> "Arkivera objekt",
	"actupload"			=> "Ladda upp fil(er)",

	// misc
	"miscitems"			=> "Objekt",
	"miscfree"			=> "Fri",
	"miscusername"		=> "Anv�ndarnamn",
	"miscpassword"		=> "L�senord",
	"miscoldpass"		=> "Gammalt l�senord",
	"miscnewpass"		=> "Nytt l�senord",
	"miscconfpass"		=> "Bekr�fta l�senord",
	"miscconfnewpass"	=> "Bekr�fta nytt l�senord",
	"miscchpass"		=> "Byt l�senord",
	"mischomedir"		=> "Hemkatalog",
	"mischomeurl"		=> "Hem-URL",
	"miscshowhidden"	=> "Visa dolda objekt",
	"mischidepattern"	=> "Hide pattern",
	"miscperms"			=> "R�ttigheter",
	"miscuseritems"		=> "(namn, hemkatalog, visa dolda objekt, r�ttigheter, aktiv)",
	"miscadduser"		=> "l�gg till anv�ndare",
	"miscedituser"		=> "redigera anv�ndare'%s'",
	"miscactive"		=> "Aktiv",
	"misclang"			=> "Spr�k",
	"miscnoresult"		=> "Inga resultat till�ngliga.",
	"miscsubdirs"		=> "S�k i underkataloger",
	"miscpermnames"		=> array("Visa bara","�ndra","�ndra l�senord","�ndra & Byt l�senord",	"Administrat�r"),
	"miscyesno"			=> array("Ja","Nej","J","N"),
	"miscchmod"			=> array("�gare", "Grupp", "Publik"),

	// from here all new by mic
	'miscowner'			=> '�gare',
	'miscownerdesc'		=> '<strong>Beskrivning:</strong><br />Anv�ndare (UID) /<br />Grupp (GID)<br />Aktuella r�ttigheter:<br /><strong> %s ( %s ) </strong>/<br /><strong> %s ( %s )</strong>',

	// sysinfo (new by mic)
	'simamsysinfo'		=> "eXtplorer systeminfo",
	'sisysteminfo'		=> 'Systeminfo',
	'sibuilton'			=> 'Operativsystem',
	'sidbversion'		=> 'Databasversion (MySQL)',
	'siphpversion'		=> 'PHP-version',
	'siphpupdate'		=> 'INFORMATION: <span style="color: red;">PHP-version du anv�nder �r<strong>inte</strong> aktuell!</span><br />F�r att garantera att alla funktioner och m�jligheter ska fungera,<br />b�r du anv�nda minst <strong>PHP-version 4.3</strong>!',
	'siwebserver'		=> 'Webbserver',
	'siwebsphpif'		=> 'Webberverns PHP-interface',
	'simamboversion'	=> 'eXtplorer-version',
	'siuseragent'		=> 'Browserversion',
	'sirelevantsettings' => 'Viktiga PHP-inst�llningar',
	'sisafemode'		=> 'Safe Mode',
	'sibasedir'			=> 'Open basedir',
	'sidisplayerrors'	=> 'PHP Errors',
	'sishortopentags'	=> 'Short Open Tags',
	'sifileuploads'		=> 'File Uploads',
	'simagicquotes'		=> 'Magic Quotes',
	'siregglobals'		=> 'Register Globals',
	'sioutputbuf'		=> 'Output Buffer',
	'sisesssavepath'	=> 'Session Savepath',
	'sisessautostart'	=> 'Session auto start',
	'sixmlenabled'		=> 'XML enabled',
	'sizlibenabled'		=> 'ZLIB enabled',
	'sidisabledfuncs'	=> 'Disabled functions',
	'sieditor'			=> 'WYSIWYG-editor',
	'siconfigfile'		=> 'Configfil',
	'siphpinfo'			=> 'PHP-info',
	'siphpinformation'	=> 'PHP-information',
	'sipermissions'		=> 'R�ttigheter',
	'sidirperms'		=> 'Katalogr�ttigheter',
	'sidirpermsmess'	=> 'F�r att vara s�ker att alla funktioner och m�jligheter i eXtplorer ska fungera korrekt, ska f�ljande kataloger ha skrivr�ttigheter [chmod 0777]',
	'sionoff'			=> array( 'Av', 'P�' ),
	
	'extract_warning' => "Vill du verkigen packa upp denna fil h�r?<br />Detta kan komma att resultera i att befintliga filer skrivs �ver om du �r of�rsiktig!",
	'extract_success' => "Uppackningen lyckades",
	'extract_failure' => "Uppackningen misslyckades",
	
	'overwrite_files' => 'Skriv �ver befintliga fil(er)?',
	"viewlink"		=> "Visa",
	"actview"		=> "Visa filen",
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to fun_chmod.php file
	'recurse_subdirs'	=> 'Till�mpa p� underkataloger?',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to footer.php file
	'check_version'	=> 'Kontrollera om det finns uppdateringar',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to fun_rename.php file
	'rename_file'	=>	'Byt namn p� katalog eller fil...',
	'newname'		=>	'Nytt namn',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to fun_edit.php file
	'returndir'	=>	'�terv�nd till katalog efter spara?',
	'line'		=> 	'Linje',
	'column'	=>	'Kolumn',
	'wordwrap'	=>	'Radbrytning (endast IE)',
	'copyfile'	=>	'Kopiera fil in i detta filnamn',
	
	// Bookmarks
	'quick_jump' => 'Hoppa till',
	'already_bookmarked' => 'Denna katalog �r redan bokm�rkt',
	'bookmark_was_added' => 'Denna katalog lades till p� bokm�rkeslistan.',
	'not_a_bookmark' => 'Denna katalog �r inte bokm�rkt.',
	'bookmark_was_removed' => 'Denna katalog togs bort fr�n bokm�rkeslistan.',
	'bookmarkfile_not_writable' => "Det gick inte att bokm�rka .\n Bokm�rkesfilen '%s' �r inte skrivbar \n.",
	
	'lbl_add_bookmark' => 'L�gg till denna katalog som bokm�rke',
	'lbl_remove_bookmark' => 'Ta bort denna katalog fr�n bokm�rkeslistan',
	
	'enter_alias_name' => 'Ange ett alias f�r detta bokm�rke',
	
	'normal_compression' => 'normal komprimering',
	'good_compression' => 'god komprimering',
	'best_compression' => 'b�sta komprimering',
	'no_compression' => 'inge komprimering',
	
	'creating_archive' => 'Skapa arkivfil...',
	'processed_x_files' => 'Prossessat %s av %s filer',
	
	'ftp_header' => 'Lokala FTP-inst�llningar',
	'ftp_login_lbl' => 'Ange dina loginuppgifter f�r FTP-servern',
	'ftp_login_name' => 'FTP-anv�ndarnamn ',
	'ftp_login_pass' => 'FTP-l�senord',
	'ftp_hostname_port' => 'FTP-host och port<br />(Port �r en valfri uppgift)',
	'ftp_login_check' => 'Kontrollerar FTP-anslutning...',
	'ftp_connection_failed' => "Det gick inte att koppla upp mot FTP-servern. \nKontrollera att FTP-servern �r ig�ng p� din server.",
	'ftp_login_failed' => "FTP-inloggningen misslyckades. Kontrollera anv�ndarnamn och l�senord och f�rs�k igen.",
		
	'switch_file_mode' => 'Aktuellt l�ge �r [%s] <br /> �ndra till [%s] l�ge.',
	'symlink_target' => 'M�l f�r Symbolic Link',
	
	"permchange"		=> "CHMOD lyckades:",
	"savefile"		=> "Filen sparades.",
	"moveitem"		=> "Flyttning lyckades.",
	"copyitem"		=> "Kopiering lyckades.",
	'archive_name' 	=> 'Namn p� arkivfil',
	'archive_saveToDir' 	=> 'Spara arkivet i denna katalog',
	
	'editor_simple'	=> 'Editorl�ge',
	'editor_syntaxhighlight'	=> 'L�ge f�r kodmarkering',

	'newlink'	=> 'Ny Fil/Katalog',
	'show_directories' => 'Visa kataloger',
	'actlogin_success' => 'Inloggning lyckades!',
	'actlogin_failure' => 'Inloggning misslyckades, f�rs�k igen.',
	'directory_tree' => 'Katalogtr�d',
	'browsing_directory' => 'Bl�ddra i katalogerna',
	'filter_grid' => 'Filter',
	'paging_page' => 'Sida',
	'paging_of_X' => 'av {0}',
	'paging_firstpage' => 'F�rsta sidan',
	'paging_lastpage' => 'Sista sidan',
	'paging_nextpage' => 'N�sta sidan',
	'paging_prevpage' => 'F�reg�ende sidan',
	
	'paging_info' => 'Visa objekt {0} - {1} av {2}',
	'paging_noitems' => 'Inga objekt att visa',
	'aboutlink' => 'Om...',
	'password_warning_title' => 'Viktigt - �ndra ditt l�senord!',
	'password_warning_text' => 'Anv�ndarkontot du loggade in med (admin med l�senord admin) �r standardkontot f�r administrat�rer i eXtplorer. Din eXtplorer-installation ligger �ppen f�r angrepp och du m�ste omedelbart r�tta till detta s�kerhetsh�l. Byt l�senord nu!',
	'change_password_success' => 'Ditt l�senord har �ndrats!',
	'success' => 'Lyckades',
	'failure' => 'Misslyckades',
	'dialog_title' => 'Webbsitedialog',
	'upload_processing' => 'Processar uppladdning, v�nta...',
	'upload_completed' => 'Uppladdning lyckades!',
	'acttransfer' => '�verf�r fr�n en annan server',
	'transfer_processing' => 'Processar �verf�ring fr�n Server-till-Server, v�nta...',
	'transfer_completed' => '�verf�ring klar!',
	'max_file_size' => 'Maximal filstorlek',
	'max_post_size' => 'Maximal uppladdningsgr�ns',
	'done' => 'Klart.',
	'permissions_processing' => 'Applicerar r�ttigheter, v�nta...',
	'archive_created' => 'Arkivfilen har skapats!',
	'save_processing' => 'Sparar fil...',
	'current_user' => 'Detta skript k�rs just nu med r�ttigheter f�r f�ljande anv�ndare:',
	'your_version' => 'Din version',
	'search_processing' => 'S�ker, v�nta...',
	'url_to_file' => 'Filens URL',
	'file' => 'Fil'
	
);
?>
