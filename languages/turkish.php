<?php

// Turkish Language for eXtplorer v2.1.12 (Translated by Mehmet Taş)
global $_VERSION;

$GLOBALS["charset"] = "UTF-8";
$GLOBALS["text_dir"] = "ltr"; // ('ltr' for left to right, 'rtl' for right to left)
$GLOBALS["date_fmt"] = "Y/m/d H:i";
$GLOBALS["error_msg"] = array(
	// error
	"error"			=> "Hata(lar)",
	"message"		=> "Mesaj(lar)",
	"back"			=> "Geri Al",
	
	// root
	"home"			=> "Ana klasör oluşturulmamış. Ayarlarınızı kontrol edin.",
	"abovehome"		=> "Bu klasör ana klasörün üstünde olmamalıdır.",
	"targetabovehome"	=> "Hedef klasör ana klasörün üstünde olmamalıdır.",
	
	// exist
	"direxist"		=> "Bu klasör oluşturulamadı.",
	//"filedoesexist"	=> "Bu dosya zaten var.",
	"fileexist"		=> "Bu dosya oluşturulamadı.",
	"itemdoesexist"		=> "Bu öğe zaten var.",
	"itemexist"		=> "Bu öğe mevcut değil.",
	"targetexist"		=> "Hedef klasör mevcut değil.",
	"targetdoesexist"	=> "Hedef öğe zaten mevcut.",
	
	// open
	"opendir"		=> "Klasör Açılamaz.",
	"readdir"		=> "Klasör Okunamaz.",
	
	// access
	"accessdir"		=> "Bu klasöre ulaşmak için izinleriniz yetersiz.",
	"accessfile"		=> "Bu dosyaya ulaşmak için izinleriniz yetersiz.",
	"accessitem"		=> "Bu öğeye ulaşmak için izinleriniz yetersiz.",
	"accessfunc"		=> "Bu fonksiyonu kullanmak için izinleriniz yetersiz.",
	"accesstarget"		=> "Hedef klasöre ulaşmak için izinleriniz yetersiz.",
	
	// actions
	"permread"		=> "İzin gösterimi başarısız.",
	"permchange"		=> "İzin değiştirme başarısız. (Nedeni dosya sahiplik sorunu olabilir.)",
	"openfile"		=> "Dosya açılması başarısız.",
	"savefile"		=> "Dosya kaydı başarısız.",
	"createfile"		=> "Dosya oluşturma başarısız.",
	"createdir"		=> "Klasör oluşturma başarısız.",
	"uploadfile"		=> "Dosya yüklemesi başarısız.",
	"copyitem"		=> "Kopyalama başarısız.",
	"moveitem"		=> "Taşıma başarısız.",
	"delitem"		=> "Silme başarısız.",
	"chpass"		=> "Şifre değiştirme başarısız.",
	"deluser"		=> "Kullanıcı kaldırma başarısız.",
	"adduser"		=> "Kullanıcı ekleme başarısız.",
	"saveuser"		=> "Kullanıcı değişiklik kaydı başarısız.",
	"searchnothing"		=> "Aramak için bir değer girmelisiniz.",
	
	// misc
	"miscnofunc"		=> "Fonksiyon kullanılabilir.",
	"miscfilesize"		=> "Dosya en büyük boyutu aştı.",
	"miscfilepart"		=> "Dosyanın yalnızca bir kısmı yüklenebildi.",
	"miscnoname"		=> "Bir isim girmelisiniz.",
	"miscselitems"		=> "Hiçbir öğe(ler) seçmediniz.",
	"miscdelitems"		=> " {0} ogeyi silmek istediginizden eminmisiniz?",
	"miscdeluser"		=> "'{0}' kullanıcısını silmek istediğinizden eminmisiniz?",
	"miscnopassdiff"	=> "Yeni şifre eskisinden farklı değil.",
	"miscnopassmatch"	=> "Şifreler eşleşmiyor.",
	"miscfieldmissed"	=> "Gerekli bir boşluğu atladınız.",
	"miscnouserpass"	=> "Kullanıcı adı yada şifreniz yanlış.",
	"miscselfremove"	=> "Kendinizi silemezsiniz.",
	"miscuserexist"		=> "Kullanıcı zaten var.",
	"miscnofinduser"	=> "Kullanıcı bulunamadı.",
	"extract_noarchive" => "Dosya uygun arşivlenmemiş.",
	"extract_unknowntype" => "Bilinmeyen arşiv türü",

	'chmod_none_not_allowed' => 'İzinleri <hiçbiri> olarak değiştirmek yasaktır',
	'archive_dir_notexists' => 'Belirlediğiniz Kaydetme Dizini mevcut değil.',
	'archive_dir_unwritable' => 'Arşiv kaydetmek için lütfen yazılabilir dizin belirtin.',
	'archive_creation_failed' => 'Arşiv Dosyasını Kaydetme Başarısız'
	
);
$GLOBALS["messages"] = array(
	// links
	"permlink"		=> "İzinleri Değiştir",
	"editlink"		=> "Değiştir",
	"downlink"		=> "İndir",
	"uplink"		=> "Yukarı",
	"homelink"		=> "Anadizin",
	"reloadlink"		=> "Yenile",
	"copylink"		=> "Kopyala",
	"movelink"		=> "Taşı",
	"dellink"		=> "Sil",
	"comprlink"		=> "Sıkıştır",
	"adminlink"		=> "Yönetici",
	"logoutlink"		=> "Çıkış",
	"uploadlink"		=> "Yükle",
	"searchlink"		=> "Ara",
	"extractlink"	        => "Arşivi Çıkart",
	'chmodlink'		=> 'İzinleri (Chmod) Değiştir', // new mic
	'mossysinfolink'	=> 'eXtplorer Sistem Bilgisi (eXtplorer, Server, PHP, mySQL)', // new mic
	'logolink'		=> 'eXtplorer Websitesine Git (yeni pencerede)', // new mic
	
	// list
	"nameheader"		=> "İsim",
	"sizeheader"		=> "Boyut",
	"typeheader"		=> "Tip",
	"modifheader"		=> "Değiştirme",
	"permheader"		=> "İzinler",
	"actionheader"		=> "Hareketler",
	"pathheader"		=> "Yol",
	
	// buttons
	"btncancel"		=> "Çıkış",
	"btnsave"		=> "Kaydet",
	"btnchange"		=> "Değiştir",
	"btnreset"		=> "Sıfırla",
	"btnclose"		=> "Kapat",
	"btncreate"		=> "Oluştur",
	"btnsearch"		=> "Ara",
	"btnupload"		=> "Yükle",
	"btncopy"		=> "Kopyala",
	"btnmove"		=> "Taşı",
	"btnlogin"		=> "Giriş",
	"btnlogout"		=> "Çıkış",
	"btnadd"		=> "Ekle",
	"btnedit"		=> "Değiştir",
	"btnremove"		=> "Kaldır",
	
	// user messages, new in eXtplorer 1.3.0
	'renamelink'	=> 'Yeniden Adlandır',
	'confirm_delete_file' => 'Bu dosyayı silmek istediğinizden emin misiniz? <br />%s',
	'success_delete_file' => 'Öğe(ler) başarıyla silindi.',
	'success_rename_file' => 'Dizin/dosya %s başarıyla yeniden adlandırıldı  %s.',
	
	// actions
	"actdir"		=> "Klasör",
	"actperms"		=> "İzinleri Değiştir",
	"actedit"		=> "Dosyayı Düzenle",
	"actsearchresults"	=> "Arama Sonuçları",
	"actcopyitems"		=> "öğe(ler)i kopyala",
	"actcopyfrom"		=> " /%s dan /%s ya ",
	"actmoveitems"		=> "öğe(ler)i taşı",
	"actmovefrom"		=> " /%s dan /%s ya taşı ",
	"actlogin"		=> "Giriş",
	"actloginheader"	=> "eXtplorer kullanmak için giriş yapın",
	"actadmin"		=> "Yönetim",
	"actchpwd"		=> "Şifre Değiştir",
	"actusers"		=> "Kullanıcılar",
	"actarchive"		=> "öğe(ler)i Yedekle",
	"actupload"		=> "Dosya(ları) Yükle",
	
	// misc
	"miscitems"		=> "öğe(ler)",
	"miscfree"		=> "Serbest",
	"miscusername"		=> "Kullanıcı Adı",
	"miscpassword"		=> "Şifre",
	"miscoldpass"		=> "Eski Şifre",
	"miscnewpass"		=> "Yeni Şifre",
	"miscconfpass"		=> "Şifreyi Onaylayın",
	"miscconfnewpass"	=> "Yeni Şifeyi Onaylayın",
	"miscchpass"		=> "Şifre Değiştir",
	"mischomedir"		=> "Ana Klasör",
	"mischomeurl"		=> "Baş URL",
	"miscshowhidden"	=> "Gizli Öğeleri Gösterin",
	"mischidepattern"	=> "Resim Gizle",
	"miscperms"		=> "İzinler",
	"miscuseritems"		=> "(isim, ana klasör, gizli öğeleri göster, izinler, Aktif)",
	"miscadduser"		=> "Kullanıcı ekleyin",
	"miscedituser"		=> "'%s' kullanıcısını değiştirin",
	"miscactive"		=> "Aktif",
	"misclang"		=> "Dil",
	"miscnoresult"		=> "Hiç sonuç yok.",
	"miscsubdirs"		=> "Alt kategorileri de arayın",
	"miscpermnames"		=> array("Sadece bakılabilir","Modifiye","Şifre değiştir","Modifiye & Şifre Değiştir",
					"Yönetici"),
	"miscyesno"		=> array("Evet","Hayır","E","H"),
	"miscchmod"		=> array("Sahip", "Grup", "Genel"),

	// from here all new by mic
	'miscowner'			=> 'Sahip',
	'miscownerdesc'		=> '<strong>Açıklama:</strong><br />Kullanıcı (UID) /<br />Grup (GID)<br />Mevcut Haklar:<br /><strong> %s ( %s ) </strong>/<br /><strong> %s ( %s )</strong>',

	// sysinfo (new by mic)
	'simamsysinfo'		=> "eXtplorer Sistem Bilgisi",
	'sisysteminfo'		=> 'Sistem Bilgisi',
	'sibuilton'			=> 'İşletim Sistemi',
	'sidbversion'		=> 'Veritabanı Sürümü (MySQL)',
	'siphpversion'		=> 'PHP Sürümü',
	'siphpupdate'		=> 'Bilgi: <span style="color: red;">Kullandığınız PHP sürümü <strong>güncel değil!</strong></span><br />'.$_VERSION->PRODUCT.' tüm ekleriyle fonksiyonlarını ve özelliklerini garantilemek için <br />en düşük <strong>PHP sürümü 4.3</strong> kullanmalısınız!',
	'siwebserver'		=> 'Web Sunucu',
	'siwebsphpif'		=> 'WebServer - PHP Interface',
	'simamboversion'	=> 'eXtplorer Sürümü',
	'siuseragent'		=> 'Tarayıcı Sürümü',
	'sirelevantsettings' => 'Önemli PHP Ayarları',
	'sisafemode'		=> 'Güvenli Mod',
	'sibasedir'			=> 'Open basedir',
	'sidisplayerrors'	=> 'PHP Hataları',
	'sishortopentags'	=> 'Kısa Açık Etiketler',
	'sifileuploads'		=> 'Dosya Yüklemeleri',
	'simagicquotes'		=> 'Magic Quotes',
	'siregglobals'		=> 'Genel Kayıtlar',
	'sioutputbuf'		=> 'Output Buffer',
	'sisesssavepath'	=> 'Session Savepath',
	'sisessautostart'	=> 'Session auto start',
	'sixmlenabled'		=> 'XML Etkin',
	'sizlibenabled'		=> 'ZLIB Etkin',
	'sidisabledfuncs'	=> 'Etkin Olmayan İşlevler',
	'sieditor'			=> 'WYSIWYG Editör',
	'siconfigfile'		=> 'Yapılandırma Dosyası',
	'siphpinfo'			=> 'PHP Bilgisi',
	'siphpinformation'	=> 'PHP Information',
	'sipermissions'		=> 'İzinler',
	'sidirperms'		=> 'Dizin izinleri',
	'sidirpermsmess'	=> 'Şu uygulamanın '.$_VERSION->PRODUCT.' tüm işlevleri ve özelliklerinin doğru çalıştığından emin olmak için, aşağıdaki klasörlerin yazma izinleri [chmod 0777] olmalıdır',
	'sionoff'		=> array( 'Açık', 'Kapalı' ),
	
	'extract_warning'	=> "Gerçekten bu dosyayı buraya çıkartmak istiyor musunuz?<br />Dikkatli kullanılmadığında varolan dosyaların üzerine yazılacak!",
	'extract_success'	=> "Arşiv Çıkartma Başarılı",
	'extract_failure'	=> "Arşiv Çıkartma Başarısız",
        


'overwrite_files' => 'Varolan dosyanın üzerine yaz?',
	"viewlink"		=> "Göster",
	"actview"		=> "Kaynak dosyasını göster",
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to fun_chmod.php file
	'recurse_subdirs'	=> 'Alt Klasörlere Uygulansın mı?',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to footer.php file
	'check_version'	=> 'Son sürümü kontrol et',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to fun_rename.php file
	'rename_file'	=>	'Dizini yada dosyayı yenidien adlandır...',
	'newname'		=>	'Yeni İsim',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to fun_edit.php file
	'returndir'	=>	'Kaydettikten sonra dizine geri dönülsün mü?',
	'line'		=> 	'Satır',
	'column'	=>	'Sütun',
	'wordwrap'	=>	'Sözcük Kaydırma: (yalnız IE)',
	'copyfile'	=>	'Dosyayı, bu dosya adına kopyalayın',
	
	// Bookmarks
	'quick_jump' => 'Hızlı Atlama',
	'already_bookmarked' => 'Bu dizin zaten yer imlerinde',
	'bookmark_was_added' => 'Bu dizin yer imleri listesine eklendi.',
	'not_a_bookmark' => 'Bu dizin yer imlerinde değil.',
	'bookmark_was_removed' => 'Bu dizin yer imleri listesinden çıkartıldı.',
	'bookmarkfile_not_writable' => "Yer imi % s konumuna getirilemedi. \n Yer İmi Dosyası '%s' \nis yazılabilir değil.",
	
	'lbl_add_bookmark' => 'Bu dizini yerimlerine ekle',
	'lbl_remove_bookmark' => 'Bu dizini yer imleri listesinden çıkar',
	
	'enter_alias_name' => 'Lütfen bu yer imi için takma ad girin',
	
	'normal_compression' => 'normal sıkıştırma',
	'good_compression' => 'İyi Sıkıştırma',
	'best_compression' => 'En İyi Sıkıştırma',
	'no_compression' => 'sıkıştırma yok',
	
	'creating_archive' => 'Arşiv dosyası oluştur...',
	'processed_x_files' => 'Processed %s of %s Files',
	
	'ftp_header' => 'Yerel FTP Kimlik Doğrulama',
	'ftp_login_lbl' => 'FTP Sunucusu Oturum Açma Kimlik Bilgilerini Giriniz',
	
	'ftp_login_name' => 'FTP Kullanıcı Adı',
	'ftp_login_pass' => 'FTP Şifresi',
	'ftp_hostname_port' => 'FTP Sunucusu',
	'ftp_login_check' => 'FTP bağlantısı Kontrol ediliyor...',
	'ftp_connection_failed' => "FTP sunucusuna bağlanılamadı. \nFTP sunucusunun çalışıp çalışmadığını kontrol edin.",
	'ftp_login_failed' => "FTP girişinde hata. Lütfen kullanıcı adı ve şifrenizi kontrol ederek tekrar deneyin.",
		
	'switch_file_mode' => 'Şimdiki Mod: <strong>%s</strong>. Dilerseniz %s moduna geçebilirsiniz. <br/> Türkçe Çeviri: <a href="https://durualan.com" target="_blank" rel="noopener"><b>Mehmet TAŞ</b></a>',
	'symlink_target' => 'Sembolik Bağlantı Hedefi',
	
	"permchange"		=> "Yazma İzinleri Başarılı:",
	"savefile"		=> "Dosya kaydedildi.",
	"moveitem"		=> "Taşıma Tamamlandı.",
	"copyitem"		=> "Kopyalama Tamamlandı.",
	'archive_name' 	=> 'Arşiv dosyası ismi',
	'archive_saveToDir' 	=> 'Arşivi bu dizine kaydet',
	
	'editor_simple'	=> 'Basit Editör Modu',
	'editor_syntaxhighlight'	=> 'Syntax-Highlighted Mode',

	'newlink'	=> 'Yeni Dosya/Dizin',
	'show_directories' => 'Dizinleri Göster',
	'actlogin_success' => 'Giriş başarılı!',
	'actlogin_failure' => 'Giriş hatalı, tekrar deneyin.',
	'directory_tree' => 'Dizin Ağacı',
	'browsing_directory' => 'Dizinlere Gözatın',
	'filter_grid' => 'Filtreleyin',
	'paging_page' => 'Sayfa',
	'paging_of_X' => 'of {0}',
	'paging_firstpage' => 'İlk Sayfa',
	'paging_lastpage' => 'Son Sayfa',
	'paging_nextpage' => 'Sonraki Sayfa',
	'paging_prevpage' => 'Önceki Sayfa',
	
	'paging_info' => 'Gösterilen Öğeler {0} - {1} of {2}',
	'paging_noitems' => 'Gösterilecek öğe yok',
	'aboutlink' => 'Hakkında...',
	'password_warning_title' => 'Önemli - Şifrenizi değiştirin!',
	'password_warning_text' => 'Giriş yapmış olduğunuz kullanıcı hesabı, varsayılan eXtplorer tarafından ayrılan hesaba karşılık gelir. EXtplorer kurulumunuz izinsiz girişe açık ve bu güvenlik açığını hemen düzeltmelisiniz!',
	'change_password_success' => 'Şifreniz değiştirildi!',
	'success' => 'Başarılı',
	'failure' => 'Hata',
	'dialog_title' => 'Website Uyarısı',
	'upload_processing' => 'Yükleniyor, lütfen bekleyin...',
	'upload_completed' => 'Yükleme Başarılı!',
	'acttransfer' => 'Başka Sunucudan Transfer Edin',
	'transfer_processing' => 'Sunucudan sunucuya transfer sürüyor, lütfen bekleyin...',
	'transfer_completed' => 'Transfer tamamlandı!',
	'max_file_size' => 'En Büyük Dosya Boyutu',
	'max_post_size' => 'En Büyük Yükleme Sınırı',
	'done' => 'Tamam.',
	'permissions_processing' => 'İzinler uygulanıyor, lütfen bekleyin...',
	'archive_created' => 'Arşiv Dosyası Oluşturuldu!',
	'save_processing' => 'Dosya Kaydediliyor...',
	'current_user' => 'Bu uygulama şu anda aşağıdaki kullanıcıların izinleriyle çalışır:',
	'your_version' => 'Sizin Sürümünüz',
	'search_processing' => 'Aranıyor, lütfen bekleyin...',
	'url_to_file' => 'Dosya adresi',
	'file' => 'Dosya'

);
?>
