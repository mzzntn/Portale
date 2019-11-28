<?php
	/**
	 * language pack
	 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
	 * @link www.phpletter.com
	 * @since 22/April/2007
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Aggiorna');
		define("LBL_ACTION_DELETE", 'Cancella');
		define('LBL_ACTION_CUT', 'Taglia');
		define('LBL_ACTION_COPY', 'Copia');
		define('LBL_ACTION_PASTE', 'Incolla');
		define('LBL_ACTION_CLOSE', 'Chiudi');
		//File Listing
	define('LBL_NAME', 'Nome');
	define('LBL_SIZE', 'Dimensione');
	define('LBL_MODIFIED', 'Modificato il');
		//File Information
	define('LBL_FILE_INFO', 'Informazioni file:');
	define('LBL_FILE_NAME', 'Nome:');	
	define('LBL_FILE_CREATED', 'Creato il:');
	define("LBL_FILE_MODIFIED", 'Modificato il:');
	define("LBL_FILE_SIZE", 'Dimensione:');
	define('LBL_FILE_TYPE', 'Tipo file:');
	define("LBL_FILE_WRITABLE", 'Scrivibile?');
	define("LBL_FILE_READABLE", 'Leggibile?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Informazioni Cartella');
	define("LBL_FOLDER_PATH", 'Percorso:');
	define("LBL_FOLDER_CREATED", 'Creata il:');
	define("LBL_FOLDER_MODIFIED", 'Modificata il:');
	define('LBL_FOLDER_SUDDIR', 'Sottocartelle:');
	define("LBL_FOLDER_FIELS", 'Files:');
	define("LBL_FOLDER_WRITABLE", 'Scrivibile?');
	define("LBL_FOLDER_READABLE", 'Leggibile?');
		//Preview
	define("LBL_PREVIEW", 'Anteprima');
	define('LBL_CLICK_PREVIEW', 'Clicca qui per anteprima.');
	//Buttons
	define('LBL_BTN_SELECT', 'Seleziona');
	define('LBL_BTN_CANCEL', 'Annulla');
	define("LBL_BTN_UPLOAD", 'Upload');
	define('LBL_BTN_CREATE', 'Crea');
	define('LBL_BTN_CLOSE', 'Chiudi');
	define("LBL_BTN_NEW_FOLDER", 'Nuova Cartella');
	define('LBL_BTN_EDIT_IMAGE', 'Modifica');
	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Nessun documento selezionato per tagliare.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Nessun documento selezionato per copiare');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Nessun documento selezionato per incollare.');
	define('WARNING_CUT_PASTE', 'Sei sicuro di voler spostare i documenti selezionati nella cartella corrente?');
	define('WARNING_COPY_PASTE', 'Sei sicuro di voler copiare i documenti selezionati nella cartella corrente');
	
	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Per favore, seleziona un file.');
	define('ERR_NOT_DOC_SELECTED', 'Nessun documento selezionato per cancellare.');
	define('ERR_DELTED_FAILED', 'Impossible cancellare i documenti selezionati).');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Questo percorso non è permesso.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Impossible trovare la cartella: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Per favore, scegli un nome che contiene solo lettere, numeri, spazi, trattini e underscore.');
	define('ERR_RENAME_EXISTS', 'Per favore, scegli un nome che non sia già assegnato.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Questo file/cartella non esiste');
	define('ERR_RENAME_FAILED', 'Impossibile rinominare.');
	define('ERR_RENAME_EMPTY', 'Per favore, scegli un noome.');
	define("ERR_NO_CHANGES_MADE", 'Non è stata fatta alcuna modifica.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Non hai i permessi per assegnare questa estensione al file.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Per favore, scegli un nome che contiene solo lettere, numeri, spazi, trattini e underscore.');
	define('ERR_FOLDER_EXISTS', 'Per favore, scegli un nome che non sia già assegnato.');
	define('ERR_FOLDER_CREATION_FAILED', 'Impossibile creare la cartella.');
	define('ERR_FOLDER_NAME_EMPTY', 'Per favore, scegli un nome.');
	
		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Per favore, scegli un nome che contiene solo lettere, numeri, spazi, trattini e underscore.');
	define('ERR_FILE_NOT_UPLOADED', 'Nessun file selezionato per l\\\'upload.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Non hai i permessi per fare l\\\'upload di un file con questa estensione.');
	define('ERR_FILE_MOVE_FAILED', 'Impossibile spostare il file.');
	define('ERR_FILE_NOT_AVAILABLE', 'Il file non è disponibile.');
	define('ERROR_FILE_TOO_BID', 'Il file è troppo grande. (massimo: %s)');
	//file download
	define('ERR_DOWNLOAD_FILE_NOT_FOUND', 'Nessun file selezionato per scaricare.');
	

	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Clicca una volta per aprire in questa cartella...');
	define("TIP_DOC_RENAME", 'Clicca due volte per modificare...');
	define('TIP_FOLDER_GO_UP', 'Clicca una volta per andare alla cartella superiore...');
	define("TIP_SELECT_ALL", 'Seleziona Tutti');
	define("TIP_UNSELECT_ALL", 'Deseleziona Tutti');
	//WARNING
	define('WARNING_DELETE', 'Sei sicuro di voler cancellare i file selezionati?');
	define('WARNING_IMAGE_EDIT', 'Per favore, scegli un\\\'immagine da modificare.');
	define('WARNING_NOT_FILE_EDIT', 'Per favore, scegli un file da modificare.');
	define('WARING_WINDOW_CLOSE', 'Sei sicuro di voler chiudere la finestra?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Anteprima non disponibile.');
	define('PREVIEW_OPEN_FAILED', 'Impossibile aprire il file.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Impossibile caricare l\\\'immagine');

	//Login
	define('LOGIN_PAGE_TITLE', 'Form di Login per Ajax File Manager');
	define('LOGIN_FORM_TITLE', 'Form di Login');
	define('LOGIN_USERNAME', 'Nome utente:');
	define('LOGIN_PASSWORD', 'Password:');
	define('LOGIN_FAILED', 'Nome utente/password non validi.');
	
	
	//88888888888   Below for Image Editor   888888888888888888888
		//Warning 
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', "Non hai effettuato alcuna modifica all\'immagine.");
		
		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'L\\\'immagine non esiste');
		define('IMG_WARNING_LOST_CHANAGES', 'Tutte le modifiche non salvate saranno perse. Sei sicuro di voler continuare?');
		define('IMG_WARNING_REST', 'Tutte le modifiche non salvate saranno perse. Sei sicuro di voler resettare?');
		define('IMG_WARNING_EMPTY_RESET', 'L\\\'immagine non è ancora stata modificata');
		define('IMG_WARING_WIN_CLOSE', 'Sei sicuro di voler chiudere la finestra?');
		define('IMG_WARNING_UNDO', 'Sei sicuro di voler riportare l\\\'immagine allo stato precedente?');
		define('IMG_WARING_FLIP_H', 'Sei sicuro di voler ribaltare orizzontalmente l\\\'immagine?');
		define('IMG_WARING_FLIP_V', 'Sei sicuro di voler ribaltare verticalmente l\\\'immagine?');
		define('IMG_INFO', 'Informazioni sull&quot;immagine');
		
		//Mode
			define('IMG_MODE_RESIZE', 'Ridimensiona:');
			define('IMG_MODE_CROP', 'Ritaglia:');
			define('IMG_MODE_ROTATE', 'Ruota:');
			define('IMG_MODE_FLIP', 'Ribalta:');		
		//Button
		
			define('IMG_BTN_ROTATE_LEFT', '90&deg;SO');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg;SAO');
			define('IMG_BTN_FLIP_H', 'Ribalta Orizzontalmente');
			define('IMG_BTN_FLIP_V', 'Ribalta Verticalmente');
			define('IMG_BTN_RESET', 'Resetta');
			define('IMG_BTN_UNDO', 'Annulla');
			define('IMG_BTN_SAVE', 'Salva');
			define('IMG_BTN_CLOSE', 'Chiudi');
			define('IMG_BTN_SAVE_AS', 'Salva con nome');
			define('IMG_BTN_CANCEL', 'Annulla');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', 'Limita?');
		//Label
			define('IMG_LBL_WIDTH', 'Larghezza:');
			define('IMG_LBL_HEIGHT', 'Altezza:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'Percentuale:');
			define('IMG_LBL_ANGLE', 'Angolo:');
			define('IMG_LBL_NEW_NAME', 'Nuovo Nome:');
			define('IMG_LBL_SAVE_AS', 'Salva con Nome:');
			define('IMG_LBL_SAVE_TO', 'Salva in:');
			define('IMG_LBL_ROOT_FOLDER', 'Cartella Radice');
		//Editor
		//Save as 
		define('IMG_NEW_NAME_COMMENTS', 'Per favore, non includere l&quot;estensione nel nome del file.');
		define('IMG_SAVE_AS_ERR_NAME_INVALID', 'Per favore, scegli un nome che contiene solo lettere, numeri, spazi, trattini e underscore.');
		define('IMG_SAVE_AS_NOT_FOLDER_SELECTED', 'Nessuna cartella di destinazione selezionata.');	
		define('IMG_SAVE_AS_FOLDER_NOT_FOUND', 'La cartella di destinazione non esiste.');
		define('IMG_SAVE_AS_NEW_IMAGE_EXISTS', 'È già presente un&quot;immagine con questo nome.');

		//Save
		define('IMG_SAVE_EMPTY_PATH', 'Il percorso è vuoto.');
		define('IMG_SAVE_NOT_EXISTS', 'L\\\'immagine è inesistente.');
		define('IMG_SAVE_PATH_DISALLOWED', 'Non hai i permessi per accedere a questo file.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Modalità di operazione sull&quot;immagine inaspettata');
		define('IMG_SAVE_RESIZE_FAILED', 'Il ridimensionamento dell&quot;immagine è fallito.');
		define('IMG_SAVE_CROP_FAILED', 'Il ritaglio dell\\\'immagine è fallito');
		define('IMG_SAVE_FAILED', 'Il salvataggio dell\\\'immagine è fallito.');
		define('IMG_SAVE_BACKUP_FAILED', 'Impossibile creare il backup dell&quot;immagine originale.');
		define('IMG_SAVE_ROTATE_FAILED', 'Impossibile ruotare l\\\'immagine.');
		define('IMG_SAVE_FLIP_FAILED', 'Impossible ribaltare l\\\'immagine.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'Impossibile aprire l\\\'immagine dalla sessione.');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'Impossibile aprire l\\\'immagine');
		
		
		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'Nessuna azione presente in cronologia per annullare.');
		define('IMG_UNDO_COPY_FAILED', 'Impossibile ripristinare l\\\'immagine.');
		define('IMG_UNDO_DEL_FAILED', 'Impossibile cancellare l\\\'immagine di sessione');
	
	//88888888888   Above for Image Editor   888888888888888888888
	
	//88888888888   Session   888888888888888888888
		define("SESSION_PERSONAL_DIR_NOT_FOUND", 'Impossibile trovare la cartella dedicata che dovrebbe essere stata creata nella cartella di sessione.');
		define("SESSION_COUNTER_FILE_CREATE_FAILED", 'Impossibile aprire il contatore di sessione.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Impossibile scrivere sul contatore di sessione');
	//88888888888   Session   888888888888888888888
	
	//88888888888   Below for Text Editor   888888888888888888888
		define('TXT_FILE_NOT_FOUND', 'File non trovato.');
		define('TXT_EXT_NOT_SELECTED', 'Per favore, scegli un\\\'estensione');
		define('TXT_DEST_FOLDER_NOT_SELECTED', 'Per favore, scegli una cartella di destinazione');
		define('TXT_UNKNOWN_REQUEST', 'Richiesta sconosciuta.');
		define('TXT_DISALLOWED_EXT', 'Non hai i permessi per modificare un file di questo tipo.');
		define('TXT_FILE_EXIST', 'Questo file è già esistente.');
		define('TXT_FILE_NOT_EXIST', 'File non trovato.');
		define('TXT_CREATE_FAILED', 'Impossibile creare un nuovo file.');
		define('TXT_CONTENT_WRITE_FAILED', 'Errore nello scrivere sul file.');
		define('TXT_FILE_OPEN_FAILED', 'Impossibile aprire il file.');
		define('TXT_CONTENT_UPDATE_FAILED', 'Impossibile modificare il file.');
		define('TXT_SAVE_AS_ERR_NAME_INVALID', 'Per favore, scegli un nome che contiene solo lettere, numeri, spazi, trattini e underscore.');
	//88888888888   Above for Text Editor   888888888888888888888
	
	
?>