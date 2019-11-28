    <!-- right column !-->
    <div id="rightcolumn" class="column menu">
        <h3>Accesso Diretto alle Pratiche</h3>
			  <div class="menucnt">    
            <form class="smallform" action='<?=URL_APP_PRATICHE?>/pratica.php' method='post'>
                <p><label for="codDiretto">Password</label><input type='text' name='codDiretto' id="codDiretto" size="10"></p>
                <p><label for="codDirettoCf">C.F.</label><input type='text' name='codDirettoCf' id='codDirettoCf' size="10"></p>
                <div class="buttons">
                    <input class="button" type='submit' name='Ok' value='Ok'>
                </div>    
            </form>
        </div>    
    </div>
    <!-- /right column !-->
