# DESCRIZIONE DEL PORTALE DEI SERVIZI DEL COMUNE DI ALESSANDRIA
Il portale del comune di Alessandria è stato pensato per erogare servizi ai cittadini.
----------------------

Nel dettaglio i servizi che sono stati resi disponibili sono i seguenti.

## Appuntamenti online
Il sistema consente al cittadino, una volta effettuato l'accesso, di vedere le agende con le relative disponibilità che l'ente mette a disposizione, e prendere un appuntamento. Alla conferma il cittadino riceve un email iCal ed inserisce automaticamente l'appuntamento nel proprio calendario.

Successivamente il cittadino può, prima dell'appuntamento, annullare lo stesso.

L'ente dal canto suo oltre a poter definire sportelli/operatori, tipologie di richieste con durate diverse fra loro, definire i giorni e le fasce orarie di disponibilità ed inserire giorni di chiusura, ha una console di amministrazione per vedere il dettaglio di tutti gli appuntamenti con la possibilità di inserire note e di annullare a sua volta.
## Comunicazioni
L'ente può inviare comunicazioni ai cittadini che si sono attivati nel portale mediante di tipo pubblico o privato, utilizzando oltre al portale stesso, anche diversi canali di comunicazione.

Il comune di Alessandria ha attivato:
* Email
* Twitter
* Facebook

Le comunicazioni pubbliche vengono inserite all'interno del portale, nelle pagine social se selezionate ed inviate a tutti via email se selezionata.

Le comunicazioni private, invece, vengono inserite nell'apposita sezione privata del portale di ciascun utente oltre che tramite email se selezionata.

Il tutto nel rispetto delle normative vigenti sulla privacy, vedi informativa specifica del comune di Alessandria: l'utente, infatti, deve autorizzare il trattamento per questo servizio ed accettare l'invio delle comunicazioni. Tutte opzioni che può disabilitare un autonomia dal proprio account.

## Modulistica Online
Il servizio consente al cittadino di compilare le varie tipologie di istanza che l'ente ha messo a disposizione, utilizzando un semplice e potente editor.

Il sistema offre tutta una serie di opzioni e parametri che consentono di gestire qualsiasi tipologia di istanza semplice ovvero dove c'è un singolo richiedente come ad esempio l'iscrizione ad un servizio su richiesta, un accesso agli atti, un bando di un concorso etc.

L'istanza viene poi inviata poi inviata all'ente tramite email o pec, con un messaggio semplice o interoperabile (segnatura.xml) per la protocollazione automatica.

Verso il cittadino, il sistema produce la comunicazione di avvio del procedimento ai sensi dell'art. 18 bis della Legge 241/90.

E' inoltre possibile, oltre a definire la visibilità temporale delle varie istanze, definirne la modalità di perfezionamento ovvero:
* solo invio dopo compilazione
* apposizione della firma digitale
* apposizione della firma digitale o autografa

Qualora l'utente abbia effettuato l'accesso con SPID, anche se è prevista l'apposizione di una firma, il sistema non la richiederà in ottemperanza a quanto previsto dalla norma.

## Sistema di autenticazione
Il sistema ha un suo sistema di registrazione ed autenticazione ed è integrato con SPID ed EIDAS.

SPID ed EIDAS sono integrati al livello 2 ovvero con l'accesso a 2 fattori: utente e password e OTP

## Gestione del backoffice  
L'ente accede ad un'unica piattaforma dalla quale è in grado di gestire tutte le varie funzionalità di amministrazione.

Esistono uno o più amministratori globali che gestiscono le autorizzazioni e che vedono tutti i dati degli utenti, mentre gli altri operatori vedono solo la parte dei informazione necessaria per l'espletamento delle proprie funzioni.

## ARCHITETTURA DEL SOFTWARE ED INSTALLAZIONE
Vedi documentazione nella cartella docs

## ULTERIORI INFORMAZIONI
Nell'installazione di base non sono presenti le seguenti funzionalità in quanto necessitano di una configurazione particola e personalizzata per ciascun ente che adotta la soluzione.

Nel dettaglio:
* Autenticazione cittadino con SPID => necessaria attivazione da parte di AGID nominativa sull'ente.
* Autenticazione cittadino con EIDAS => necessaria attivazione da parte di AGID nominativa sull'ente.
* Pubblicazione Comunicazione su Facebook => necessaria procedura di abilitazione su facebook sulla pagina dell'ente.
* Pubblicazione Comunicazione su Twitter => necessaria configurazione per la pagina dell'ente.

Come si può constatare dai punti precedenti il sistema ha bisogno di un sottodominio dedicato e di un certificato SSL in modo da attivare la navigazione in https, prerequisito anche per l'attivazione di SPID ed EIDAS.
## Documentazione
Trattandosi di servizi online, non è previsto un manuale per il cittadino, ma è direttamente il sistema che pensato per essere semplice ed intuitivo e quindi immediatamente utilizzabile da parte del cittadino.

Per gli operatori  dell'ente, invece, sono previsti i manuali d'uso dedicati per ciascun ambito operativo reperibili nella cartella documentazione (/var/www/siti/portale/documentazione/):
* manuale_portal_rev2.pdf => Amministrazione degli utenti e dei servizi
* manuale_appuntamenti_rev1.pdf => Gestione appuntamenti e configurazione
* manuale_moduli_rev5.pdf => Amministrazione modulistica online
* manuale_comunicazioni_rev1.pdf  => Gestione Comunicazioni  

## Sviluppatore

Il software è prodotto da Dedagroup Public Services Srl, contattabile mediante ticket scrivendo a helpdesk.pa@dedagroup.it
