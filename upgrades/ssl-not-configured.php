<?php

function tot_ssl_not_configured_notice() {
    if( tot_is_production() == true){
        ?>
         <div class="notice-error notice is-dismissible tot-dismiss-notice " data-notice="error_activation">
             <h2>Error!</h2>
             <p>
                 The Identity Verification Plugin by Token of Trust needs your help before it can be used in Live Mode.
                 <br><br>Your SSL configuration is missing required certificate authority files. You can fix this by downloading 'cacert.pem' certificates from
                 <a href="https://curl.haxx.se/docs/caextract.html">https://curl.haxx.se/docs/caextract.html</a>. Place the '.pem' file in a location accessible to Wordpress and record the full path (here we'll assume 'path/to/certificate/cacert.pem'). Finally, add the following line to your php.ini server file (replacing the pem path with the correct location of the pem file you just downloaded):
             </p>

             <pre>curl.cainfo = "path/to/certificate/cacert.pem"</pre>

         </div>
        <?php
    } else {
        ?>
        <div class="notice-warning notice is-dismissible tot-dismiss-notice " data-notice="error_activation">
        <h2>Warning!</h2>
        <p>The Identity Verification Plugin by Token of Trust was successfully set up to be used in Test Mode.

            <br><br>However, before you can use Token of Trust in Live Mode, you will need to fix your SSL configuration. You can do this by downloading 'cacert.pem' certificates from
            <a href="https://curl.haxx.se/docs/caextract.html">https://curl.haxx.se/docs/caextract.html</a>. Place the '.pem' file in a location accessible to Wordpress and record the full path (here we'll assume 'path/to/certificate/cacert.pem'). Finally, add the following line to your php.ini server file (replacing the pem path with the correct location of the pem file you just downloaded):
        </p>

        <pre>curl.cainfo = "path/to/certificate/cacert.pem".</pre>
        </div>
        <?php
    }
}
