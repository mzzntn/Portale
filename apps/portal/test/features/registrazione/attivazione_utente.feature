# language: it
Funzionalità: Attivazione utente
  Come utente

  Scenario: Attivazione automatica
    Dato che la attivazione utenti automatica è attiva
    E che il controllo cellulare alla registrazione non è attivo
    E che mi sono registrato al portale
    E che ho ricevuto la e-mail di conferma indirizzo e-mail
    Quando clicco sul link di conferma
    Allora il mio utente deve essere in stato "attivo"
    
  Scenario: Ricezione notifica utente per amministratore
    Dato che la attivazione utenti automatica non è attiva
    E che il controllo cellulare alla registrazione non è attivo
    E che mi sono registrato al portale
    E che ho ricevuto la e-mail di conferma indirizzo e-mail
    Quando clicco sul link di conferma
    Allora l'amministratore deve ricevere una notifica dell'iscrizione
    
  Scenario: Attivazione utente senza controllo cellulare
    Dato che la attivazione utenti automatica non è attiva
    E che il controllo cellulare alla registrazione non è attivo
    E che mi sono registrato al portale
    E che ho ricevuto la e-mail di conferma indirizzo e-mail
    Quando clicco sul link di conferma
    E l'amministratore riceve la notifica dell'iscrizione
    E l'amministratore attiva l'utente della notifica
    Allora il mio utente deve essere in stato "attivo"
    E devo ricevere una notifica dell'attivazione