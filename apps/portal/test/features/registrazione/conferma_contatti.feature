# language: it
Funzionalità: Conferma contatti

  Scenario: Invio conferma e-mail registrazione
    Quando mi registro al portale
    Allora devo ricevere la e-mail di conferma indirizzo e-mail
    
  Scenario: Conferma e-mail registrazione
    Dato che la attivazione utenti automatica non è attiva
    E che il controllo cellulare alla registrazione non è attivo
    E che mi sono registrato al portale
    E che ho ricevuto la e-mail di conferma indirizzo e-mail
    Quando clicco sul link di conferma
    Allora il mio utente deve essere in stato "attesa"
    E l'amministratore deve ricevere una notifica dell'iscrizione

  Scenario: Invio sms conferma registrazione
    Dato che il controllo cellulare alla registrazione è attivo
    E che mi sono registrato al portale
    Allora devo ricevere l'sms di conferma numero di cellulare

  Scenario: Conferma cellulare registrazione
    Dato che la attivazione utenti automatica non è attiva
    E che il controllo cellulare alla registrazione è attivo
    E che il controllo e-mail alla registrazione non è attivo
    E che mi sono registrato al portale
    E che ho ricevuto l'sms di conferma numero di cellulare
    Quando vado al link di conferma cellulare e inserisco il codice di conferma
    Allora il mio utente deve essere in stato "attesa"
    E l'amministratore deve ricevere una notifica dell'iscrizione

  Scenario: SMS conferma cambio cellulare
    Dato che il controllo cambio cellulare è attivo
    E che sono iscritto al portale
    E che il mio cellulare è confermato
    Quando cambio il mio numero di cellulare
    Allora il mio cellulare deve essere non confermato
    E devo ricevere l'sms di conferma numero di cellulare
