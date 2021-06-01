<?php

// Malay Language Module (translated by Hj Ahmad Rasyid Hj Ismail "ahrasis" ahrasis@gmail.com http://sahabat.ahrasis.com http://ahrasis.sch.my)

global $_VERSION;

$GLOBALS['charset'] = 'UTF-8';
$GLOBALS['text_dir']= 'ltr'; // ('ltr' for left ke right, 'rtl' for right ke left)
$GLOBALS['date_fmt']= 'd/m/Y H:i';
$GLOBALS['error_msg'] = array(
	// error
	'error'	=> 'Kesalahan',
	'message'	=> 'Mesej',
	'back'	=> 'Kembali',

	// root
	'home'	=> 'Direktori home tiada, semak tetapan anda.',
	'abovehome'	=> 'Direktori ini mungkin tiada di atas direktori home.',
	'targetabovehome'	=> 'Direktori sasaran mungkin tiada di atas direktori home.',

	// exist
	'direxist'	=> 'Direktori ini sudah ada.',
	//'filedoesexist'	=> 'Fail ini sudah ada.',
	'fileexist'	=> 'Fail ini tidak wujud.',
	'itemdoesexist'	=> 'Item ini sudah ada.',
	'itemexist'	=> 'Item ini tidak wujud.',
	'targetexist'	=> 'Direktori sasaran tidak wujud.',
	'targetdoesexist'	=> 'Direktori sasaran sudah ada.',

	// open
	'opendir'	=> 'Tidak dapat membuka direktori.',
	'readdir'	=> 'Tidak dapat membaca direktori.',

	// access
	'accessdir'	=> 'Anda tidak diizinkan untuk mengakses direktori ini.',
	'accessfile'	=> 'Anda tidak diizinkan untuk mengakses fail ini.',
	'accessitem'	=> 'Anda tidak diizinkan untuk mengakses item ini.',
	'accessfunc'	=> 'Anda tidak diizinkan untuk menggunakan fungsi ini.',
	'accesstarget'	=> 'Anda tidak diizinkan untuk mengakses direktori sasaran.',

	// actions
	'permread'	=> 'Gagal mendapatkan keizinan.',
	'permchange'	=> 'CHMOD gagal (Biasanya disebabkan masalah milikan fail - contoh : Jika pengguna HTTP ('wwwrun' atau 'nobody') dan pengguna FTP tidak sama)',
	'openfile'	=> 'Gagal membuka fail.',
	'savefile'	=> 'Gagal menyimpan fail.',
	'createfile'	=> 'Gagal mencipta fail.',
	'createdir'	=> 'Gagal mencipta direktori.',
	'uploadfile'	=> 'Gagal memuatnaik fail.',
	'copyitem'	=> 'Gagal menyalin item.',
	'moveitem'	=> 'Gagal memindah item.',
	'delitem'	=> 'Gagal menghapus item.',
	'chpass'	=> 'Gagal mengubah kata sandi.',
	'deluser'	=> 'Gagal menghapus pengguna.',
	'adduser'	=> 'Gagal menambah pengguna.',
	'saveuser'	=> 'Gagal menyimpan pengguna.',
	'searchnothing'	=> 'Isikan sesuatu bagi mencari.',

	// misc
	'miscnofunc'	=> 'Fungsi tidak tersedia.',
	'miscfilesize'	=> 'Fail melebihi ukuran maksima.',
	'miscfilepart'	=> 'Hanya sebahagian fail dimuatnaik.',
	'miscnoname'	=> 'Anda mesti berikan nama.',
	'miscselitems'	=> 'Anda tidak memilih item.',
	'miscdelitems'	=> 'Anda pasti mahu menghapus {0} item ini?',
	'miscdeluser' 	=> 'Anda pasti mahu menghapus pengguna '{0}'?',
	'miscnopassdiff'	=> 'Kata sandi baru sama dengan kata sandi sedia ada.',
	'miscnopassmatch'	=> 'Kata sandi tidak sesuai.',
	'miscfieldmissed'	=> 'Anda tertinggal kolum penting.',
	'miscnouserpass'	=> 'Nama pengguna atau kata sandi tidak tepat.',
	'miscselfremove'	=> 'Anda tidak boleh menghapus diri sendiri.',
	'miscuserexist'	=> 'Pengguna sudah ada.',
	'miscnofinduser'	=> 'Tidak dapat menemukan pengguna.',
	'extract_noarchive'	=> 'Fail arkib tidak boleh diekstrak.',
	'extract_unknowntype'	=> 'Jenis arkib tidak diketahui.',
	
	'chmod_none_not_allowed'	=> 'Mengubah izin pada <none> tidak diperbolehkan.',
	'archive_dir_notexists'	=> 'Direktori Simpanan yang anda tentukan tidak wujud.',
	'archive_dir_unwritable'	=> 'Sila tentukan direktori yang dapat ditulis bagimenyimpan arkib.',
	'archive_creation_failed'	=> 'Gagal menyimpan fail arkib.'

);
$GLOBALS['messages'] = array(
	// links
	'permlink'	=> 'Ubah Izin',
	'editlink'	=> 'Edit',
	'downlink'	=> 'Muatturun',
	'uplink'	=> 'Atas',
	'homelink'	=> 'Home',
	'reloadlink'	=> 'Segar',
	'copylink'	=> 'Salin',
	'movelink'	=> 'Pindah',
	'dellink'	=> 'Hapus',
	'comprlink'	=> 'Bina Arkib',
	'adminlink'	=> 'Pengurus',
	'logoutlink'	=> 'Keluar',
	'uploadlink'	=> 'Muatnaik',
	'searchlink'	=> 'Cari',
	'difflink'	=> 'Beza',
	'extractlink'	=> 'Ekstrak Arkib',
	'chmodlink'	=> 'Ubah milikan (chmod folder / fail)', // new mic
	'mossysinfolink'	=> 'Sisten Info eXtplorer (eXtplorer, Server, PHP, mySQL)', // new mic
	'logolink'	=> 'Buka laman eXtplorer (di jendela baru)', // new mic

	// list
	'nameheader'	=> 'Nama',
	'sizeheader'	=> 'Saiz',
	'typeheader'	=> 'Jenis',
	'modifheader'	=> 'Diubah',
	'permheader'	=> 'Izin',
	'actionheader'	=> 'Aksi',
	'pathheader'	=> 'Laluan',

	// buttons
	'btncancel'	=> 'Batal',
	'btnsave'	=> 'Simpan',
	'btnchange'	=> 'Ganti',
	'btnreset'	=> 'Reset',
	'btnclose'	=> 'Tutup',
	'btnreopen'	=> 'Buka Semula',
	'btncreate'	=> 'Cipta',
	'btnsearch'	=> 'Cari',
	'btnupload'	=> 'Muatnaik',
	'btncopy'	=> 'Salin',
	'btnmove'	=> 'Pindah',
	'btnlogin'	=> 'Masuk',
	'btnlogout'	=> 'Keluar',
	'btnadd'	=> 'Tambah',
	'btnedit'	=> 'Edit',
	'btnremove'	=> 'Hapus',
	'btndiff'	=> 'Beza',
	
	// pengguna messages, new in eXtplorer 1.3.0
	'renamelink'	=> 'Namakan Semula',
	'confirm_delete_file'	=> 'Anda pasti mahu menghapuskan fail ini? <br />%s',
	'success_delete_file'	=> 'Fail berjaya dihapuskan.',
	'success_rename_file'	=> 'Direktori / fail %s berjaya diubah menjadi %s.',
	
	// actions
	'actdir'	 => 'Direktori',
	'actperms'	 => 'Ubah izin',
	'actedit'	 => 'Ubah fail',
	'actsearchresults'	=> 'Hasil Carian',
	'actcopyitems'	 => 'Salin item',
	'actcopyfrom'	 => 'Salin dari /%s ke /%s ',
	'actmoveitems'	 => 'Pindah item',
	'actmovefrom'	 => 'Pindah dari /%s ke /%s ',
	'actlogin'	 => 'Masuk',
	'actloginheader'	 => 'Masuk untuk menggunakan eXtplorer',
	'actadmin'	 => 'Pengurusan',
	'actchpwd'	 => 'Ubah kata sandi',
	'actusers'	 => 'Pengguna',
	'actarchive'	 => 'Arkibkan item',
	'actupload'	 => 'Muatnaik fail',

	// misc
	'miscitems'	=> 'Item',
	'miscfree'	=> 'Bebas',
	'miscusername'	=> 'Nama pengguna',
	'miscpassword'	=> 'Kata sandi',
	'miscoldpass'	=> 'Kata sandi lama',
	'miscnewpass'	=> 'Kata sandi baru',
	'miscconfpass'	=> 'Pastikan kata sandi',
	'miscconfnewpass'	=> 'Pastikan kata sandi baru',
	'miscchpass'	=> 'Ubah kata sandi',
	'mischomedir'	=> 'Direktori Home',
	'mischomeurl'	=> 'URL Home',
	'miscshowhidden'	=> 'Paparkan item tersembunyi',
	'mischidepattern'	=> 'Pola tersembunyi',
	'miscperms'	=> 'Izin',
	'miscuseritems'	=> '(nama, direktori home, paparkan item tersembunyi, keizinan, aktif)',
	'miscadduser'	=> 'tambah pengguna',
	'miscedituser'	=> 'ubah pengguna '%s'',
	'miscactive'	=> 'Aktif',
	'misclang'	=> 'Bahasa',
	'miscnoresult'	=> 'Tiada hasil diperolehi.',
	'miscsubdirs'	=> 'Cari subdirektori',
	'miscpermnames'	=> array('Hanya melihat','Mengubah','Ubah Kata Sandi','Ubah & Ganti Kata Sandi','Pengurusan'),
	'miscyesno'	=> array('Ya','Tidak','Y','T'),
	'miscchmod'	=> array('Pemilik', 'Grup', 'Publik'),
	'misccontent'	=> 'Isi fail',

	// from here all new by mic
	'miscowner'	=> 'Pemilik',
	'miscownerdesc'	=> '<strong>Deskripsi:</strong><br />Pengguna (UID) /<br />Grup (GID)<br />Hak Semasa:<br /><strong> %s ( %s ) </strong>/<br /><strong> %s ( %s )</strong>',

	// sysinfo (new by mic)
	'simamsysinfo'	=> 'Info Sistem eXtplorer',
	'sisysteminfo'	=> 'Info Sistem',
	'sibuilton'	=> 'Sistem Operasi',
	'sidbversion'	=> 'Versi Database (MySQL)',
	'siphpversion'	=> 'Versi PHP',
	'siphpupdate'	=> 'INFO: <span style='color: red;'>Versi PHP anda ialah <strong>tidak</strong> tepat!</span><br />Bagi menjamin semua fungsi dan ciri berjalan baik,<br />anda perlu menggunakan minima <strong>Versi PHP 5.3</strong>!',
	'siwebserver'	=> 'Serverlaman',
	'siwebsphpif'	=> 'Serverlaman - Antaramuka PHP',
	'simamboversion'	=> 'Versi eXtplorer',
	'siuseragent'	=> 'Versi Browser',
	'sirelevantsettings'	=> 'Tetapan Penting PHP',
	'sisafemode'	=> 'Mod Selamat',
	'sibasedir'	=> 'Open Basedir',
	'sidisplayerrors'	=> 'Kesalahan PHP',
	'sishortopentags'	=> 'Tag Terbuka Pendek',
	'sifileuploads'	=> 'Muatnaik Fail',
	'simagicquotes'	=> 'Magic Quotes',
	'siregglobals'	=> 'Register Globals',
	'sioutputbuf'	=> 'Buffer Output',
	'sisesssavepath'	=> 'Session Savepath',
	'sisessautostart'	=> 'Session Auto Start',
	'sixmlenabled'	=> 'XML dihidupkan',
	'sizlibenabled'	=> 'ZLIB dihidupkan',
	'sidisabledfuncs'	=> 'Fungsi dimatikan',
	'sieditor'	=> 'Editor WYSIWYG',
	'siconfigfile'	=> 'Fail Configurasi',
	'siphpinfo'	=> 'Info PHP',
	'siphpinformation'	=> 'Info PHP',
	'sipermissions'	=> 'Keizinan',
	'sidirperms'	=> 'Keizinan Direktori',
	'sidirpermsmess'	=> 'Bagi memastikan semua fungsi dan ciri eXtplorer berjalan baik, folder berikut mesti mempunyai keizinan menulis [chmod 0777]',
	'sionoff'	=> array( 'On', 'Off' ),
	
	'extract_warning'	=> 'Anda pasti mahu mengekstrak fail ini di sini?<br />Ini akan menggantikan fail sedia ada sekiranya tidak digunakan dengan berhati-hati!',
	'extract_success'	=> 'Ekstrak berjaya',
	'extract_failure'	=> 'Ekstrak gagal',
	
	'overwrite_files'	=> 'Gantikan fail sedia ada?',
	'viewlink'	=> 'Lihat',
	'actview'	=> 'Paparkan sumber fail',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to fun_chmod.php file
	'recurse_subdirs'	=> 'Libatkan ke subdirektori?',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to footer.php file
	'check_version'	=> 'Semak versi terkini',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to fun_rename.php file
	'rename_file'	=>	'Ubah nama direktori atau fail...',
	'newname'	=>	'Nama baru',
	
	// added by Paulino Michelazzo (paulino@michelazzo.com.br) to fun_edit.php file
	'returndir'	=>	'Kembali ke direktori setelah disimpan?',
	'line'	=> 	'Baris',
	'column'	=>	'Kolum',
	'wordwrap'	=>	'Susunkata: (IE sahaja)',
	'copyfile'	=>	'Salin fail ke nama berikut',
	
	// Bookmarks
	'quick_jump'	=> 'Jalan pintas ke',
	'already_bookmarked'	=> 'Direktori ini sudah ditandabuku',
	'bookmark_was_added'	=> 'Direktori ini telah ditambah dalam senarai penandabuku',
	'not_a_bookmark'	=> 'Direktori ini bukan penandabuku.',
	'bookmark_was_removed'	=> 'Direktori ini telah dihapus daripada senarai penandabuku.',
	'bookmarkfile_not_writable'	=> 'Gagal untuk menandabuku %s.\n Fail penandabuku '%s' \ntidak dapat ditulis.',
	
	'lbl_add_bookmark'	=> 'Tambahkan direktori ini sebagai penandabuku',
	'lbl_remove_bookmark'	=> 'Hapuskan direktori ini daripada senarai penandabuku',
	
	'enter_alias_name'	=> 'Sila masukkan nama alias bagi penandabuku ini',
	
	'normal_compression'	=> 'mampatan biasa',
	'good_compression'	=> 'mampatan baik',
	'best_compression'	=> 'mampatan terbaik',
	'no_compression'	=> 'tiada mampatan',
	
	'creating_archive'	=> 'Cipta fail arkib...',
	'processed_x_files'	=> 'Fail %s daripada %s diproses',
	
	'ftp_header'	=> 'Autentikasi FTP Lokal',
	'ftp_login_lbl'	=> 'Sila masukkan login pada Server FTP',
	'ftp_login_name'	=> 'Nama Pengguna FTP',
	'ftp_login_pass'	=> 'Kata Sandi FTP',
	'ftp_hostname_port'	=> 'Nama hos dan Port Server FTP <br />(Port tidak wajib)',
	'ftp_login_check'	=> 'Menyemak sambungan FTP...',
	'ftp_connection_failed'	=> 'Server FTP tidak dapat dihubungi. \nSila semak sama ada server FTP hidup di server anda.',
	'ftp_login_failed'	=> 'Log masuk FTP gagal. Sila semak nama pengguna dan kata sandi dan cuba lagi.',
	
	'switch_file_mode'	=> 'Mode saat ini: <strong>%s</strong>. Anda dapat mengubah ke mode %s.',
	'symlink_target'	=> 'Target dari Symbolic Link',
	
	'permchange'	=> 'CHMOD berjaya:',
	'savefile'	=> 'Fail telah disimpan.',
	'moveitem'	=> 'Berjaya memindah.',
	'copyitem'	=> 'Berjaya menyalin.',
	'archive_name'	 => 'Nama fail arkib',
	'archive_saveToDir'	=> 'Simpan arkib dalam direktori ini',
	
	'editor_simple'	=> 'Mod Editor Mudah',
	'editor_syntaxhighlight'	=> 'Mod Sintaks-Dicerahkan',

	'newlink'	=> 'Fail / Direktori baru',
	'show_directories'	=> 'Paparkan Direktori',
	'actlogin_success'	=> 'Log masuk berjaya!',
	'actlogin_failure'	=> 'Login gagal, cuba lagi.',
	'directory_tree'	=> 'Pecahan Directori',
	'browsing_directory'	=> 'Melayari Direktori',
	'filter_grid'	=> 'Saring',
	'paging_page'	=> 'Laman',
	'paging_of_X'	=> 'daripada {0}',
	'paging_firstpage'	=> 'Laman Pertama',
	'paging_lastpage'	=> 'Laman Terakhir',
	'paging_nextpage'	=> 'Laman Seterusnya',
	'paging_prevpage'	=> 'Laman Sebelumnya',
	
	'paging_info'	=> 'Memaparkan Item {0} - {1} daripada {2}',
	'paging_noitems'	=> 'Tiada item yang boleh dipaparkan',
	'aboutlink'	=> 'Tentang...',
	'password_warning_title'	=> 'Penting - Ubah kata sandi anda!',
	'password_warning_text'	=> 'Akaun pengguna anda (yang dimasuki dengan nama dan kata sandi admin:admin) adalah sama dengan akaun default eXtplorer. Ini membuka peluang pencerobohan sekiranya andatidak segera mengubahnya!',
	'change_password_success'	=> 'Kata sandi anda telah diubah!',
	'success'	=> 'Berjaya',
	'failure'	=> 'Gagal',
	'dialog_title'	=> 'Dialog Laman',
	'upload_processing'	=> 'Memuatnaik, sila tunggu...',
	'upload_completed'	=> 'Muatnaik berjaya!',
	'acttransfer'	=> 'Pemindahan daripada server lain',
	'transfer_processing'	=> 'Memproses pemindahan server-ke-server, sila tunggu...',
	'transfer_completed'	=> 'Pemindahan selesai!',
	'max_file_size'	=> 'Saiz fail maksima',
	'max_post_size'	=> 'Had muatnaik maksima',
	'done'	=> 'Selesai.',
	'permissions_processing'	=> 'Menerapkan keizinan, sila tunggu...',
	'archive_created'	=> 'Arkib failt telah siap!',
	'save_processing'	=> 'Menyimpan fail...',
	'current_user'	=> 'Skrip ini dijalankan dengan izin pengguna berikut:',
	'your_version'	=> 'Versi Anda',
	'search_processing'	=> 'Mencari, sila tunggu..',
	'url_to_file'	=> 'URL Fail',
	'file'	=> 'Fail'
	
);
?>
