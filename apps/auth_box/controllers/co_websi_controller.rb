# -*- encoding : utf-8 -*-
require 'apps/auth_box/models/co_websi_user'

module AuthBox

	class COWebsiController < Spider::PageController

    	route /login/, :login

        layout 'auth_box'
        

        def self.c_route
            'co_websi'
        end

        DIPEN = Spider.conf.get('missioni.cod_gruppo_dipendente')
        PERS  = Spider.conf.get('missioni.cod_gruppo_personale')


        def before(action='', *params)
            @scene.DIPEN = DIPEN
            @scene.PERS = PERS
            super
        end

        __.action
        def login(id_controller_chiamante)
            #id_controller_chiamante mi dice a che app poi fare il redirect se il login e andato a buon fine
            if !id_controller_chiamante.blank?  
                #se ho inviato i dati per il login
                if @request.post?
                    #se manca username o password rifaccio l'autenticazione
                    if @request.params['username_login_box'].blank? || @request.params['password_login_box'].blank?
                        redirect AuthBoxController.http_s_url("login?cod=#{id_controller_chiamante}&er=vuoti")
                    else
                        #autentico
                        #parametri da passare all'autenticazione
                        params = { :username => @request.params['username_login_box'], :password => @request.params['password_login_box'] }
                        
                        #chiamo il metodo che effettua l'auth a livello di civilia
                        c_o_websi_user = COWebsiUser.autentica(params)
                        
                        hash_dati_autenticazione = Spider.conf.get('auth_box.'+id_controller_chiamante)
                        
                        #controllo che user e password inviati siano presenti in CiviliaOpen

                        controllo = false
                        if c_o_websi_user.blank?
                            #non valida l'autenticazione
                            Spider.logger.debug("Autenticazione CiviliaOpen Websi, redirect per username o password errati")
                            redirect AuthBoxController.http_s_url("login?cod=#{id_controller_chiamante}&er=no_auth")
                        elsif (c_o_websi_user =~ /^errore/) == 0
                            Spider.logger.debug("Autenticazione CiviliaOpen Websi, problema di connessione al database")
                            redirect AuthBoxController.http_s_url("login?cod=#{id_controller_chiamante}&er=err_db")
                        else    
                            #creo in sessione l'user civilia_open_websi_user
                            civilia_open_websi_user = COWebsiUser.new
                            civilia_open_websi_user.data_login = DateTime.now.strftime('%Y%m%d%H%M%S')
                            civilia_open_websi_user.id_controller = id_controller_chiamante
                            #salvo come chiave primaria il M1_USER_COD per errore in http_controller in log_done
                            civilia_open_websi_user.primary_keys = c_o_websi_user.username
                            civilia_open_websi_user.cod_master = c_o_websi_user.cod_master.master

                            #modificata riga seguente, in quanto un responsabile può essere anche un missionario
                            #per cui è stata aggiunta la possibilità a Login (_login_box.shml che se siamo con app Missioni 
                            #viene chiesto anche il ruolo di utilizzo con il quale si vuole accedere
                            civilia_open_websi_user.cod_gruppo = @request.params['ruolo']
                            civilia_open_websi_user.cod_gruppo = "DIPEN" if c_o_websi_user.cod_gruppo != PERS || c_o_websi_user.cod_gruppo.blank? 
                            #fine modifica

                            civilia_open_websi_user.intestazione = c_o_websi_user.cod_master.to_s
                            civilia_open_websi_user.save_to_session(@request.session)
                            #non salvo perche non è un managed
                            #civilia_open_websi_user.save
                            
                            #redirect alla index del controller chiamante
                            redirect const_get_full(hash_dati_autenticazione['controller_chiamante']).http_s_url
                            done
                        end

                    end        
                else
                    #arrivo in get, passo l'id del controller del chiamante
                    redirect AuthBoxController.http_s_url("login?cod=#{id_controller_chiamante}")
                end
            else
                #redirect all'url inserito in configurazione per rifare il login
                Spider.logger.debug("Autenticazione CiviliaOpen Websi, redirect per parametri mancanti in configurazione")
                redirect AuthBoxController.http_s_url('login')
            end
          
        end


        __.action
        def logout
            cod = @request.params['cod']
            #cancello lo user da request.user e da @request.session[:auth]
            @request.session.class.delete(@request.session.sid)
            redirect AuthBox::AuthBoxController.http_s_url("login?cod=#{cod}")
        end


	end



end
