# -*- encoding : utf-8 -*-
require 'apps/auth_box/models/civilia_open_user'

module AuthBox

	class CiviliaOpenController < Spider::PageController

    	route /login/, :login

        layout 'auth_box'
        

        def self.c_route
            'civilia_open'
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
                        c_o_user = CiviliaOpenUser.autentica(params)
                        
                        hash_dati_autenticazione = Spider.conf.get('auth_box.'+id_controller_chiamante)
                        
                        #controllo che user e password inviati siano presenti in CiviliaOpen

                        controllo = false
                        if c_o_user.blank?
                            #non valida l'autenticazione
                            Spider.logger.debug("Autenticazione CiviliaOpen, redirect per username o password errati")
                            redirect AuthBoxController.http_s_url("login?cod=#{id_controller_chiamante}&er=no_auth")
                        else    
                            #creo in sessione l'user civilia_open_user
                            civilia_open_user = CiviliaOpenUser.new
                            civilia_open_user.data_login = DateTime.now.strftime('%Y%m%d%H%M%S')
                            civilia_open_user.id_controller = id_controller_chiamante
                            #salvo come chiave primaria il M1_USER_COD per errore in http_controller in log_done
                            civilia_open_user.primary_keys = c_o_user.username
                            civilia_open_user.save_to_session(@request.session)
                            #non salvo perche non è un managed
                            #civilia_open_user.save
                            
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
                Spider.logger.debug("Autenticazione CiviliaOpen, redirect per parametri mancanti in configurazione")
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
