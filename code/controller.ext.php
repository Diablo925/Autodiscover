<?php

class module_controller extends ctrl_module
{

    static $ok;
	static $domainblank;
	
    /**
     * The 'worker' methods.
     */
    static function ListDomains($uid)
    {
        global $zdbh;
		global $controller;
        $currentuser = ctrl_users::GetUserDetail($uid);
        $sql = "SELECT * FROM x_vhosts WHERE vh_acc_fk=:userid AND vh_enabled_in=1 AND vh_deleted_ts IS NULL ORDER BY vh_name_vc ASC";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':userid', $currentuser['userid']);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':userid', $currentuser['userid']);
            $res = array();
            $sql->execute();
            while ($rowdomains = $sql->fetch()) {
                $res[] = array('domain' => $rowdomains['vh_name_vc']);
            }
            return $res;
        } else {
            return false;
        }
    }
		
		static function ExecuteMake($Domain, $ImapServer, $ImapPort, $ImapTls, $Pop3Server, $Pop3Port, $Pop3Tls, $SmtpServer, $SmtpPort, $SmtpTls)
		{
			global $zdbh;
			global $controller;
			$currentuser = ctrl_users::GetUserDetail();
			$rootdir = str_replace('.', '_', $Domain);
			if(empty($Domain)) { 
			self::$domainblank = true; 
				return false; }
				else {
			if (!is_dir("/var/sentora/hostdata/". $currentuser["username"] ."/public_html/$rootdir/autodiscover/")) {
				mkdir("/var/sentora/hostdata/". $currentuser["username"] ."/public_html/$rootdir/autodiscover/", 0777);
			}
				}
				if($ImapTls == 1) { $ImapTls = "on"; } else { $ImapTls = "off"; }
				if($Pop3Tls == 1) { $Pop3Tls = "on"; } else { $Pop3Tls = "off"; }
				if($SmtpTls == 1) { $SmtpTls = "on"; } else { $SmtpTls = "off"; }
			$handle = fopen("/var/sentora/hostdata/". $currentuser["username"] ."/public_html/$rootdir/autodiscover/autodiscover.xml", "w+");
			$write = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
			fwrite($handle, $write);
			$write = "<Autodiscover xmlns=\"http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006\">\n";
			fwrite($handle, $write);
			$write = "<Response xmlns=\"http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a\">\n";
			fwrite($handle, $write);
			$write = "<Account>\n";
			fwrite($handle, $write);
			$write = "<AccountType>email</AccountType>\n";
			fwrite($handle, $write);
			$write = "<Action>settings</Action>\n";
			fwrite($handle, $write);
			// Check is Imap is in use or not
    		if(!empty($ImapServer)) {
			$write = "<Protocol>\n";
			fwrite($handle, $write);
			$write = "<Type>IMAP</Type>\n";
			fwrite($handle, $write);
			$write = "<Server>$ImapServer</Server>\n";
			fwrite($handle, $write);
			$write = "<Port>$ImapPort</Port>\n";
			fwrite($handle, $write);
			$write = "<DomainRequired>on</DomainRequired>\n";
			fwrite($handle, $write);
			$write = "<SPA>off</SPA>\n";
			fwrite($handle, $write);
			$write = "<SSL>$ImapTls</SSL>\n";
			fwrite($handle, $write);
			$write = "<AuthRequired>on</AuthRequired>\n";
			fwrite($handle, $write);
			$write = "</Protocol>\n";
			fwrite($handle, $write);
			}
			// End Imap 
			// Check is Pop3 is in use or not
    		if(!empty($Pop3Server)) {
			$write = "<Protocol>\n";
			fwrite($handle, $write);
			$write = "<Type>POP3</Type>\n";
			fwrite($handle, $write);
			$write = "<Server>$Pop3Server</Server>\n";
			fwrite($handle, $write);
			$write = "<Port>$Pop3Port</Port>\n";
			fwrite($handle, $write);
			$write = "<DomainRequired>on</DomainRequired>\n";
			fwrite($handle, $write);
			$write = "<SPA>off</SPA>\n";
			fwrite($handle, $write);
			$write = "<SSL>$Pop3Tls</SSL>\n";
			fwrite($handle, $write);
			$write = "<AuthRequired>on</AuthRequired>\n";
			fwrite($handle, $write);
			$write = "</Protocol>\n";
			fwrite($handle, $write);
			}
			// End Pop3
			// SMTP
			$write = "<Protocol>\n";
			fwrite($handle, $write);
			$write = "<Type>SMTP</Type>\n";
			fwrite($handle, $write);
			$write = "<Server>$SmtpServer</Server>\n";
			fwrite($handle, $write);
			$write = "<Port>$SmtpPort</Port>\n";
			fwrite($handle, $write);
			$write = "<DomainRequired>on</DomainRequired>\n";
			fwrite($handle, $write);
			$write = "<SPA>off</SPA>\n";
			fwrite($handle, $write);
			$write = "<SSL>$SmtpTls</SSL>\n";
			fwrite($handle, $write);
			$write = "<AuthRequired>on</AuthRequired>\n";
			fwrite($handle, $write);
			$write = "<UsePOPAuth>on</UsePOPAuth>\n";
			fwrite($handle, $write);
			$write = "<SMTPLast>on</SMTPLast>\n";
			fwrite($handle, $write);
			$write = "</Protocol>\n";
			fwrite($handle, $write);
			//SMTP end
			$write = "</Account>\n";
			fwrite($handle, $write);
			$write = "</Response>\n";
			fwrite($handle, $write);
			$write = "</Autodiscover>\n";
			fwrite($handle, $write);
			fclose($handle);
			// now finish

			self::$ok = true;
		return true;	
		}
		
	/**
     * End 'worker' methods.
     */

    /**
     * Webinterface sudo methods.
     */
	 
	static function getDomainList()
    {
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListDomains($currentuser['userid']);
    }
	
	 static function doMake()
    {
       global $controller;
        runtime_csfr::Protect();
        $currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
        if (self::ExecuteMake($formvars['inDomain'], $formvars['inImapServer'], $formvars['inImapPort'], $formvars['inImapTls'], $formvars['inPop3Server'], $formvars['inPop3Port'], $formvars['inPop3Tls'],$formvars['inSmtpServer'], $formvars['inSmtpPort'], $formvars['inSmtpTls']))
        return true;
    } 

    static function getResult()
    {
        if (self::$ok) {
            return ui_sysmessage::shout(ui_language::translate("You autodiscover is now made"), "zannounceok");
        }
		if (self::$domainblank) {
			return ui_sysmessage::shout(ui_language::translate("No domian is selected"), "zannounceerror");
        }
        return;
    }

    /**
     * Webinterface sudo methods.
     */
}
