/**
 * Russian translation
 * @author Dmitry "dio" Levashov <dio@std42.ru>
 * @version 2011-07-15
 */
if (elFinder && elFinder.prototype && typeof(elFinder.prototype.i18) == 'object') {
	elFinder.prototype.i18.ru = {
		translator : 'Dmitry "dio" Levashov &lt;dio@std42.ru&gt;',
		language   : 'Русский язык',
		direction  : 'ltr',
		dateFormat : 'd M Y H:i',
		fancyDateFormat : '$1 H:i',
		messages   : {

			/********************************** errors **********************************/
			'error'                : 'Ошибка',
			'errUnknown'           : 'Неизвестная ошибка.',
			'errUnknownCmd'        : 'Неизвестная комманда.',
			'errJqui'              : 'Отсутствуют необходимые компоненты jQuery UI - selectable, draggable и droppable.',
			'errNode'              : 'Отсутствует DOM элемент для инициализации elFinder.',
			'errURL'               : 'Некорректная настройка. Необходимо указать URL сервера.',
			'errAccess'            : 'Доступ запрещен.',
			'errConnect'           : 'Не удалось соединиться с сервером.',
			'errAbort'             : 'Соединение прервано.',
			'errTimeout'           : 'Таймаут соединения.',
			'errNotFound'          : 'Сервер не найден.',
			'errResponse'          : 'Некорректный ответ сервера.',
			'errConf'              : 'Некорректная настройка сервера.',
			'errJSON'              : 'Модуль PHP JSON не установлен.',
			'errNoVolumes'         : 'Отсутствуют корневые директории достуные для чтения.',
			'errCmdParams'         : 'Некорректные параметры комманды "$1".',
			'errDataNotJSON'       : 'Данные не формате JSON.',
			'errDataEmpty'         : 'Данные отсутствуют.',
			'errCmdReq'            : 'Для запроса к серверу необходимо указать имя комманды.',
			'errOpen'              : 'Не удалось открыть "$1".',
			'errNotFolder'         : 'Объект не является папкой.',
			'errNotFile'           : 'Объект не является файлом.',
			'errRead'              : 'Ошибка чтения "$1".',
			'errWrite'             : 'Ошибка записи "$1".',
			'errPerm'              : 'Доступ запрещен.',
			'errLocked'            : '"$1" защищен и не может быть переименован, перемещен или удален.',
			'errExists'            : 'В папке уже существует объект с именем "$1".',
			'errInvName'           : 'Недопустимое имя файла.',
			'errFolderNotFound'    : 'Папка не найдена.',
			'errFileNotFound'      : 'Файл не найден.',
			'errTrgFolderNotFound' : 'Целевая папка "$1" не найдена.',
			'errPopup'             : 'Браузер заблокировал открытие нового окна. Чтобы окрыть файл, измените настройки браузера.',
			'errMkdir'             : 'Ошибка создания папки "$1".',
			'errMkfile'            : 'Ошибка создания файла "$1".',
			'errRename'            : 'Ошибка переименования "$1".',
			'errCopyFrom'          : 'Копирование из корневой директории "$1" запрещено.',
			'errCopyTo'            : 'Копирование в корневую директорию "$1" запрещено.',
			'errUploadCommon'      : 'Ошибка загрузки файлов.',
			'errUpload'            : 'Ошибка загрузки "$1".',
			'errUploadNoFiles'     : 'Отсутствуют загруженые файлы.',
			'errMaxSize'           : 'Превышен допустимый размер загружаемых файлов.',
			'errFileMaxSize'       : 'Размер файла превышает допустимый.',
			'errUploadMime'        : 'Недопустимый тип файла.',
			'errUploadTransfer'    : 'Ошибка передачи файла "$1".', 
			'errSave'              : 'Ошибка сохранения "$1".',
			'errCopy'              : 'Ошибка копирования "$1".',
			'errMove'              : 'Ошибка перемещения "$1".',
			'errCopyInItself'      : 'Невозможно скопировать "$1" в самого себя.',
			'errRm'                : 'Ошибка удаления "$1".',
			'errExtract'           : 'Ошибка извлечения файлов из архива "$1".',
			'errArchive'           : 'Ошибка создания архива.',
			'errArcType'           : 'Неподдерживаемый тип архива.',
			'errNoArchive'         : 'Файл не является архивом допустимого типа.',
			'errCmdNoSupport'      : 'Сервер не поддерживает эту комманду.',
			'errReplByChild'       : 'Невозможно заменить папку "$1" содержащимся в ней объектом.',
			'errArcSymlinks'       : 'По соображениям безопасности запрещена распаковка архивов, содержащих ссылки (symlinks) или файлы с недопустимыми именами.', // edited 24.06.2012
			'errArcMaxSize'        : 'Размер файлов в архиве превышает максимально разрешенный.',
			'errResize'            : 'Не удалось изменить размер "$1".',
			'errUsupportType'      : 'Неподдерживаемый тип файла.',
			'errNotUTF8Content'    : 'Файл "$1" содержит текст в кодировке отличной от UTF-8 и не может быть отредактирован.',  // added 9.11.2011
			'errNetMount'          : 'Не удалось подключить "$1".',    // added 17.04.2012
			'errNetMountNoDriver'  : 'Неподдерживаемый протокол.',     // added 17.04.2012
			'errNetMountFailed'    : 'Ошибка монтирования.',           // added 17.04.2012
			'errNetMountHostReq'   : 'Host required.', // added 18.04.2012
			'errSessionExpires'    : 'Сессия была завершена так как превышено время отсутствия активности',
			'errCreatingTempDir'   : 'Ошибка при создании временной директории: "$1"',
			'errFtpDownloadFile'   : 'Ошибка при скачивании файла с FTP: "$1"',
			'errFtpUploadFile'     : 'Ошибка при загрузке файла на FTP: "$1"',
			'errFtpMkdir'          : 'Ошибка при создании директории на FTP: "$1"',
			'errArchiveExec'       : 'Ошибка при выполнении архивации: "$1"',
			'errExtractExec'       : 'Ошибка при выполнении распаковки: "$1"',

			'errUploadFile'        : 'Невозможно загрузить файл "$1"',


			/******************************* commands names ********************************/
			'cmdarchive'   : 'Создать архив',
			'cmdback'      : 'Назад',
			'cmdcopy'      : 'Копировать',
			'cmdcut'       : 'Вырезать',
			'cmddownload'  : 'Скачать',
			'cmdduplicate' : 'Сделать копию',
			'cmdedit'      : 'Редактировать',
			'cmdextract'   : 'Распаковать архив',
			'cmdforward'   : 'Вперед',
			'cmdgetfile'   : 'Выбрать',
			'cmdhelp'      : 'О программе',
			'cmdhome'      : 'Домой',
			'cmdinfo'      : 'Свойства',
			'cmdmkdir'     : 'Новая папка',
			'cmdmkfile'    : 'Новый файл',
			'cmdopen'      : 'Открыть',
			'cmdpaste'     : 'Вставить',
			'cmdquicklook' : 'Быстрый просмотр',
			'cmdreload'    : 'Обновить',
			'cmdrename'    : 'Переименовать',
			'cmdrm'        : 'Удалить',
			'cmdsearch'    : 'Поиск',
			'cmdup'        : 'Наверх',
			'cmdupload'    : 'Загрузить файлы',
			'cmdview'      : 'Вид',
			'cmdresize'    : 'Размер изображения',
			'cmdsort'      : 'Сортировать',
			
			/*********************************** buttons ***********************************/ 
			'btnClose'  : 'Закрыть',
			'btnSave'   : 'Сохранить',
			'btnRm'     : 'Удалить',
			'btnCancel' : 'Отмена',
			'btnApply'  : 'Применить',
			'btnNo'     : 'Нет',
			'btnYes'    : 'Да',
			'btnMount'  : 'Подключить',  // added 18.04.2012
			/******************************** notifications ********************************/
			'ntfopen'     : 'Открытие папки',
			'ntffile'     : 'Открытие файла',
			'ntfreload'   : 'Обновление текущей папки',
			'ntfmkdir'    : 'Создание папки',
			'ntfmkfile'   : 'Создание файла',
			'ntfrm'       : 'Удаление файлов',
			'ntfcopy'     : 'Копирование файлов',
			'ntfmove'     : 'Перемещение файлов',
			'ntfprepare'  : 'Подготовка к копированию',
			'ntfrename'   : 'Переименование файлов',
			'ntfupload'   : 'Загрузка файлов',
			'ntfdownload' : 'Скачивание файлов',
			'ntfsave'     : 'Сохранение файлов',
			'ntfarchive'  : 'Создание архива',
			'ntfextract'  : 'Распаковка архива',
			'ntfsearch'   : 'Поиск файлов',
			'ntfsmth'     : 'Занят важным делом',
			'ntfnetmount' : 'Монтирую сетевой диск', // added 18.04.2012

			/************************************ dates **********************************/
			'dateUnknown' : 'Незвестно',
			'Today'       : 'Сегодня',
			'Yesterday'   : 'Вчера',
			'Jan'         : 'Янв',
			'Feb'         : 'Фев',
			'Mar'         : 'Мар',
			'Apr'         : 'Апр',
			'May'         : 'Май',
			'Jun'         : 'Июнь',
			'Jul'         : 'Июль',
			'Aug'         : 'Авг',
			'Sep'         : 'Сен',
			'Oct'         : 'Окт',
			'Nov'         : 'Ноя',
			'Dec'         : 'Дек',
			'January'     : 'Январь',
			'February'    : 'Февраль',
			'March'       : 'Март',
			'April'       : 'Апрель',
			'May'         : 'Май',
			'June'        : 'Июнь',
			'July'        : 'Июль',
			'August'      : 'Август',
			'September'   : 'Сентябрь',
			'October'     : 'Октябрь',
			'November'    : 'Ноябрь',
			'December'    : 'Декабрь',
			'Sunday'      : 'Воскресенье', 
			'Monday'      : 'Понедельник', 
			'Tuesday'     : 'Вторник', 
			'Wednesday'   : 'Среда', 
			'Thursday'    : 'Четверг', 
			'Friday'      : 'Пятница', 
			'Saturday'    : 'Суббота',
			'Sun'         : 'Вск', 
			'Mon'         : 'Пнд', 
			'Tue'         : 'Втр', 
			'Wed'         : 'Срд', 
			'Thu'         : 'Чтв', 
			'Fri'         : 'Птн', 
			'Sat'         : 'Сбт',

			/******************************** sort variants ********************************/
			'sortname'          : 'по имени', 
			'sortkind'          : 'по типу', 
			'sortsize'          : 'по размеру',
			'sortdate'          : 'по дате',
			'sortFoldersFirst'  : 'Папки в начале',

			/********************************** messages **********************************/
			'confirmReq'      : 'Необходимо подтверждение.',
			'confirmRm'       : 'Хотите удалить файлы?<br>Действие необратимо.',
			'confirmRepl'     : 'Заменить старый файл новым?',
			'apllyAll'        : 'для всех',
			'name'            : 'Имя файла',
			'size'            : 'Размер',
			'perms'           : 'Доступ',
			'modify'          : 'Изменен',
			'kind'            : 'Тип',
			'read'            : 'чтение',
			'write'           : 'запись',
			'noaccess'        : 'нет доступа',
			'and'             : 'и',
			'unknown'         : 'неизвестно',
			'selectall'       : 'Выбрать все файлы',
			'selectfiles'     : 'Выбрать файл(ы)',
			'selectffile'     : 'Выбрать первый файл',
			'selectlfile'     : 'Выбрать последний файл',
			'viewlist'        : 'В виде списка',
			'viewicons'       : 'В виде иконок',
			'places'          : 'Избранное',
			'calc'            : 'вычисляю', 
			'path'            : 'Путь',
			'aliasfor'        : 'Указывает на',
			'locked'          : 'Защита',
			'dim'             : 'Разрешение',
			'files'           : 'Файлы',
			'folders'         : 'Папки',
			'items'           : 'Объекты',
			'yes'             : 'да',
			'no'              : 'нет',
			'link'            : 'Ссылка',
			'searcresult'     : 'Результаты поиска',  
			'selected'        : 'выбрано',
			'about'           : 'О программе',
			'shortcuts'       : 'Горячие клавиши',
			'help'            : 'Помощь',
			'webfm'           : 'Файловый менеджер для web',
			'ver'             : 'Версия',
			'protocolver'     : 'версия протокола',
			'homepage'        : 'Сайт проекта',
			'docs'            : 'Документация',
			'github'          : 'Fork us on Github',
			'twitter'         : 'Follow us in twitter',
			'facebook'        : 'Join us on facebook',
			'team'            : 'Авторы',
			'chiefdev'        : 'ведущий разработчик',
			'developer'       : 'разработчик',
			'contributor'     : 'участник',
			'maintainer'      : 'сопровождение проекта',
			'translator'      : 'переводчик',
			'icons'           : 'Иконки',
			'dontforget'      : 'и не забудьте взять своё полотенце',
			'shortcutsof'     : 'Горячие клавиши отключены',
			'dropFiles'       : 'Бросить файлы',
			'or'              : 'или',
			'selectForUpload' : 'Выбрать файлы для загрузки',
			'moveFiles'       : 'Перемещение файлов',
			'copyFiles'       : 'Копирование файлов',
			'rmFromPlaces'    : 'Удалить из избранного',
			'untitled folder' : 'новая папка',
			'untitled file.txt' : 'новый файл.txt',
			'aspectRatio'     : 'Сохранять пропорции',
			'scale'           : 'Масштаб',
			'width'           : 'Ширина',
			'height'          : 'Высота',
			'resize'          : 'Размер',
			'crop'            : 'Кадрировать',
			'rotate'          : 'Поворот',
			'rotate-cw'       : 'Поворот на 90 градусов по часовой стрелке',
			'rotate-ccw'      : 'Поворот на 90 градусов против часовой стрелке',
			'degree'          : '°',
			'netMountDialogTitle' : 'Подключить сетевой диск', // added 18.04.2012
			'protocol'            : 'Протокол', // added 18.04.2012
			'host'                : 'Хост', // added 18.04.2012
			'port'                : 'Порт', // added 18.04.2012
			'user'                : 'Пользователь', // added 18.04.2012
			'pass'                : 'Пароль', // added 18.04.2012
			/********************************** mimetypes **********************************/
			'kindUnknown'     : 'Неизвестный',
			'kindFolder'      : 'Папка',
			'kindAlias'       : 'Ссылка',
			'kindAliasBroken' : 'Битая ссылка',
			// applications
			'kindApp'         : 'Приложение',
			'kindPostscript'  : 'Документ Postscript',
			'kindMsOffice'    : 'Документ Microsoft Office',
			'kindMsWord'      : 'Документ Microsoft Word',
			'kindMsExcel'     : 'Документ Microsoft Excel',
			'kindMsPP'        : 'Презентация Microsoft Powerpoint',
			'kindOO'          : 'Документ Open Office',
			'kindAppFlash'    : 'Приложение Flash',
			'kindPDF'         : 'Документ PDF',
			'kindTorrent'     : 'Файл Bittorrent',
			'kind7z'          : 'Архив 7z',
			'kindTAR'         : 'Архив TAR',
			'kindGZIP'        : 'Архив GZIP',
			'kindBZIP'        : 'Архив BZIP',
			'kindZIP'         : 'Архив ZIP',
			'kindRAR'         : 'Архив RAR',
			'kindJAR'         : 'Файл Java JAR',
			'kindTTF'         : 'Шрифт True Type',
			'kindOTF'         : 'Шрифт Open Type',
			'kindRPM'         : 'Пакет RPM',
			// texts
			'kindText'        : 'Текстовый документ',
			'kindTextPlain'   : 'Простой текст',
			'kindPHP'         : 'Исходник PHP',
			'kindCSS'         : 'Таблицы стилей CSS',
			'kindHTML'        : 'Документ HTML',
			'kindJS'          : 'Исходник Javascript',
			'kindRTF'         : 'Rich Text Format',
			'kindC'           : 'Исходник C',
			'kindCHeader'     : 'Заголовочный файл C',
			'kindCPP'         : 'Исходник C++',
			'kindCPPHeader'   : 'Заголовочный файл C++',
			'kindShell'       : 'Unix shell script',
			'kindPython'      : 'Исходник Python',
			'kindJava'        : 'Исходник Java',
			'kindRuby'        : 'Исходник Ruby',
			'kindPerl'        : 'Исходник Perl',
			'kindSQL'         : 'Исходник SQL',
			'kindXML'         : 'XML document',
			'kindAWK'         : 'Исходник AWK',
			'kindCSV'         : 'Текст с разделителями',
			'kindDOCBOOK'     : 'Документ Docbook XML',
			// images
			'kindImage'       : 'Изображение',
			'kindBMP'         : 'Изображение BMP',
			'kindJPEG'        : 'Изображение JPEG',
			'kindGIF'         : 'Изображение GIF',
			'kindPNG'         : 'Изображение PNG',
			'kindTIFF'        : 'Изображение TIFF',
			'kindTGA'         : 'Изображение TGA',
			'kindPSD'         : 'Изображение Adobe Photoshop',
			'kindXBITMAP'     : 'Изображение X bitmap',
			'kindPXM'         : 'Изображение Pixelmator',
			// media
			'kindAudio'       : 'Аудио файл',
			'kindAudioMPEG'   : 'Аудио MPEG',
			'kindAudioMPEG4'  : 'Аудио MPEG-4',
			'kindAudioMIDI'   : 'Аудио MIDI',
			'kindAudioOGG'    : 'Аудио Ogg Vorbis',
			'kindAudioWAV'    : 'Аудио WAV',
			'AudioPlaylist'   : 'Плейлист MP3',
			'kindVideo'       : 'Видео файл',
			'kindVideoDV'     : 'Видео DV',
			'kindVideoMPEG'   : 'Видео MPEG',
			'kindVideoMPEG4'  : 'Видео MPEG-4',
			'kindVideoAVI'    : 'Видео AVI',
			'kindVideoMOV'    : 'Видео Quick Time',
			'kindVideoWM'     : 'Видео Windows Media',
			'kindVideoFlash'  : 'Видео Flash',
			'kindVideoMKV'    : 'Видео Matroska',
			'kindVideoOGG'    : 'Видео Ogg'
			,'volume_files' : 'Файлы '
		}
	}
}


 

