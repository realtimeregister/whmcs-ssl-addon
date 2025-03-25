<?php

$_LANG['token']        = ', Error Token:';
$_LANG['generalError'] = 'Etwas ist schief gelaufen. Bitte überprüfen Sie die Protokolle und wenden Sie sich an den Administrator';

// Realtime Register Ssl configuration
$_LANG['addonAA']['pagesLabels']['label']['apiConfiguration']                                          = 'Configuration';
$_LANG['addonAA']['crons']['header'] = 'Cronjobs';
$_LANG['addonAA']['crons']['explanation'] = 'Hier können Sie die Cronjobs auswählen oder abwählen, die ausgeführt werden sollen. Wir empfehlen, sie alle aktiviert zu lassen, um das beste Erlebnis zu erzielen';
//synchronization cron
$_LANG['addonAA']['crons']['cron_daily'] = 'Automatische tägliche Synchronisierung';
//processing cron
$_LANG['addonAA']['crons']['cron_processing'] = 'Automatische Synchronisierung der Auftragsabwicklung alle 5 Minuten';

//synchronization cron
$_LANG['addonAA']['crons']['cron_synchronization'] = 'Automatische Synchronisierung (stündlich)';
//summary order cron
$_LANG['addonAA']['crons']['cron_ssl_summary_stats'] = 'Aktuellen SSL-Bestellstatus laden (alle 4 Stunden)';
//customers notification and creating renewals
$_LANG['addonAA']['crons']['cron_renewal'] = 'Senden Sie Kunden Benachrichtigungen über auslaufende Dienste und erstellen Sie Verlängerungsrechnungen für Dienste, die innerhalb der ausgewählten Anzahl von Tagen ablaufen (einmal täglich)';
//customers send certificate
$_LANG['addonAA']['crons']['cron_send_certificate'] = 'Senden Sie dem Client ein Zertifikat, wenn die SSL-Bestellung in den aktiven Status wechselt (alle 3 Stunden)';
//customers send certificate
$_LANG['addonAA']['crons']['cron_price_updater'] = 'Synchronisieren Sie die WHMCS-Produktpreise mit den API-Produktpreisen (täglich)';
//customers send certificate
$_LANG['addonAA']['crons']['cron_certificate_details_updater'] = 'Zertifikatsdetails in WHMCS mit den Zertifikatsdetails in der API synchronisieren (einmal täglich)';
$_LANG['addonAA']['crons']['label'] = 'Cron-Einstellungen speichern';

//
$_LANG['addonAA']['apiConfiguration']['item']['price_rate']['label']                                   = 'Rate for currency from RealtimeRegisterSSL';
$_LANG['addonAA']['apiConfiguration']['item']['rate']['label']                                         = 'Rate';
$_LANG['addonAA']['apiConfiguration']['item']['header']                                                = 'API-Konfiguration';
$_LANG['addonAA']['apiConfiguration']['item']['api_login']['label']                                    = 'Login';
$_LANG['addonAA']['apiConfiguration']['item']['api_password']['label']                                 = 'Passwort';
$_LANG['addonAA']['apiConfiguration']['item']['tech_legend']['label']                                  = 'Technischer Kontakt';
$_LANG['addonAA']['apiConfiguration']['item']['csr_generator_legend']['label']                         = 'CSR-Generator';
$_LANG['addonAA']['apiConfiguration']['item']['display_csr_generator']['label']                        = 'Verwendung des CSR-Generators zulassen';
$_LANG['addonAA']['apiConfiguration']['item']['auto_install_panel']['']['autoInstallPanel']          = 'Automatic certificate installation';
$_LANG['addonAA']['apiConfiguration']['item']['default_csr_generator_country']['description']          = 'Die Standardauswahl';
$_LANG['addonAA']['apiConfiguration']['item']['display_ca_summary']['label']                           = 'Auftragsübersicht anzeigen';
$_LANG['addonAA']['apiConfiguration']['item']['client_area_summary_orders']['label']                   = 'Zusammenfassung der Kundenbereichsbestellungen';
$_LANG['addonAA']['apiConfiguration']['item']['validation_settings']['label']                          = 'Validierungseinstellungen';
$_LANG['addonAA']['apiConfiguration']['item']['disable_email_validation']['label']                     = 'E-Mail-Überprüfung deaktivieren';
$_LANG['addonAA']['apiConfiguration']['item']['summary_expires_soon_days']['label']                    = 'Läuft bald ab';
$_LANG['addonAA']['apiConfiguration']['item']['summary_expires_soon_days']['description']              = 'Bald ablaufende SSL-Bestellungen in Statistiken einbeziehen, wenn sie innerhalb der oben ausgewählten Anzahl von Tagen ablaufen.';
$_LANG['addonAA']['apiConfiguration']['item']['send_certificate_template']['label']                    = 'Zertifikat-E-Mail-Vorlage senden';
$_LANG['addonAA']['apiConfiguration']['item']['send_certificate_template']['description']              = 'Um ein SSL-Zertifikat über die ausgewählte Vorlage zu senden, bearbeiten Sie es und platzieren Sie das Feld {$ssl_certificate} darin.';
$_LANG['addonAA']['apiConfiguration']['item']['api_test']['description'] = 'Für Testumgebung aktivieren';
$_LANG['addonAA']['apiConfiguration']['item']['api_test']['label'] = '';
//
$_LANG['addonAA']['apiConfiguration']['item']['data_migration_legend']['label']                        = 'Daten- und Konfigurationsmigration';
$_LANG['addonAA']['apiConfiguration']['item']['data_migration']['content']                             = 'Migrieren';
$_LANG['addonAA']['apiConfiguration']['modal']['import']                                               = 'Migrieren';
$_LANG['addonAA']['apiConfiguration']['modal']['close']                                                = 'Schlie&szlig;en';
$_LANG['addonAA']['apiConfiguration']['modal']['migrationData']                                        = 'Daten & Konfiguration importieren';
$_LANG['addonAA']['apiConfiguration']['migrationOldModuleDataExixts']                                  = 'Mit dem Realtime Register SSL WHMCS-Modul sind Produkte oder Dienstleistungen verbunden:';
$_LANG['addonAA']['apiConfiguration']['migrationProductIDs']                                           = 'Produkt-IDs: ';
$_LANG['addonAA']['apiConfiguration']['migrationServiceIDs']                                           = 'Service-IDs: ';
$_LANG['addonAA']['apiConfiguration']['migrationPerformMigration']                                     = 'Führen Sie eine Datenmigration durch, um Konfiguration und Daten dem RealtimeRegisterSSL WHMCS-Modul zuzuordnen.';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfo']                                    = 'Sie sind dabei, Daten und Konfiguration vom Realtime Register SSL WHMCS-Modul zu migrieren. Dieser Vorgang ist irreversibel.';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfo2']                                   = 'Aktivitäten, die durchgeführt werden:';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfoAction'][0]                           = 'Import der Addon-Konfiguration';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfoAction'][1]                           = 'Aktualisierung bestehender Produkte (Änderung des zugewiesenen Moduls)';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfoAction'][2]                           = 'Aktualisierung bestehender Dienste (Änderung des zugewiesenen Moduls)';
$_LANG['addonAA']['apiConfiguration']['messages']['data_migration_success']                            = 'Daten und Konfiguration wurden erfolgreich importiert. Die Seite wird nach 5 Sekunden automatisch neu geladen.';
//
$_LANG['addonAA']['apiConfiguration']['item']['renewal_settings_legend']['label']                      = 'Verlängerungseinstellungen';
$_LANG['addonAA']['apiConfiguration']['item']['logs_settings_legend']['label']                      = 'Protokolleinstellungen';
$_LANG['addonAA']['apiConfiguration']['item']['auto_renew_invoice_reccuring']['label']                 = 'Wiederkehrende Bestellungen';
$_LANG['addonAA']['apiConfiguration']['item']['auto_renew_invoice_reccuring']['description']           = 'Automatisch eine Verlängerungsrechnung erstellen';
$_LANG['addonAA']['apiConfiguration']['item']['send_expiration_notification_reccuring']['label']       = '';
$_LANG['addonAA']['apiConfiguration']['item']['renew_invoice_days_reccuring']['description']           = 'Tage vor Ablauf';
$_LANG['addonAA']['apiConfiguration']['item']['send_expiration_notification_reccuring']['description'] = 'Ablaufbenachrichtigungen senden';
$_LANG['addonAA']['apiConfiguration']['item']['auto_renew_invoice_one_time']['label']                  = 'Einmalige Bestellungen';
$_LANG['addonAA']['apiConfiguration']['item']['auto_renew_invoice_one_time']['description']            = 'Automatische Verlängerungsrechnung erstellen';
$_LANG['addonAA']['apiConfiguration']['item']['send_expiration_notification_one_time']['label']        = '';
$_LANG['addonAA']['apiConfiguration']['item']['renew_invoice_days_one_time']['description']            = 'Tage vor Ablauf';
$_LANG['addonAA']['apiConfiguration']['item']['send_expiration_notification_one_time']['description']  = 'Ablaufbenachrichtigungen senden';

$_LANG['addonAA']['apiConfiguration']['item']['renewal_invoice_status_unpaid']['label']                  = '';
$_LANG['addonAA']['apiConfiguration']['item']['renewal_invoice_status_unpaid']['description']            = 'Set the status of Unpaid for renewal invoices (default is payment pending)';
$_LANG['addonAA']['apiConfiguration']['item']['automatic_processing_of_renewal_orders']['label']                  = '';
$_LANG['addonAA']['apiConfiguration']['item']['automatic_processing_of_renewal_orders']['description']            = 'Automatische Bearbeitung von Verlängerungsaufträgen';
$_LANG['addonAA']['apiConfiguration']['item']['sidebar_templates']['label']                  = 'Liste der Seiten mit sichtbarer Seitenleiste';
$_LANG['addonAA']['apiConfiguration']['item']['sidebar_templates']['description']            = 'Geben Sie eine Liste von Seiten ein, die durch ein Komma getrennt sind. Wenn Sie das Feld Sidebar leer lassen, wird es auf jeder Seite angezeigt. (Beispiel: clientareaproducts,clientareaproductdetails,clientareainvoices)';
$_LANG['addonAA']['apiConfiguration']['item']['custom_guide']['label']                  = 'Custom Guide on Product Page';
$_LANG['addonAA']['apiConfiguration']['item']['custom_guide']['description']            = '';
$_LANG['addonAA']['apiConfiguration']['item']['renew_new_order']['label']                  = '';
$_LANG['addonAA']['apiConfiguration']['item']['renew_new_order']['description']            = 'Bestellung über bestehende Bestellung verlängern';
$_LANG['addonAA']['apiConfiguration']['item']['visible_renew_button']['label']                  = '';
$_LANG['addonAA']['apiConfiguration']['item']['visible_renew_button']['description']            = 'Sichtbare Schaltfläche "Erneuern" im Kundenbereich';
$_LANG['addonAA']['apiConfiguration']['item']['save_activity_logs']['label']                  = 'Aktivitätsprotokoll';
$_LANG['addonAA']['apiConfiguration']['item']['save_activity_logs']['description']            = 'Aktivieren Sie dieses Feld, um Protokolle zu speichern';
//
$_LANG['addonAA']['apiConfiguration']['item']['tech_firstname']['label']                               = 'Vorname';
$_LANG['addonAA']['apiConfiguration']['item']['use_admin_contact']['label']                            = 'Administrative Kontaktdaten verwenden';
$_LANG['addonAA']['apiConfiguration']['item']['tech_lastname']['label']                                = 'Nachname';
$_LANG['addonAA']['apiConfiguration']['item']['tech_organization']['label']                            = 'Name der Organisation';
$_LANG['addonAA']['apiConfiguration']['item']['tech_title']['label']                                   = 'Berufsbezeichnung';
$_LANG['addonAA']['apiConfiguration']['item']['tech_addressline1']['label']                            = 'Adresse';
$_LANG['addonAA']['apiConfiguration']['item']['tech_phone']['label']                                   = 'Telefonnummer';
$_LANG['addonAA']['apiConfiguration']['item']['tech_email']['label']                                   = 'E-Mail-Adresse';
$_LANG['addonAA']['apiConfiguration']['item']['tech_city']['label']                                    = 'Stadt';
$_LANG['addonAA']['apiConfiguration']['item']['tech_country']['label']                                 = 'Land';
$_LANG['addonAA']['apiConfiguration']['item']['tech_fax']['label']                                     = 'Fax-Nummer';
$_LANG['addonAA']['apiConfiguration']['item']['tech_postalcode']['label']                              = 'PLZ';
$_LANG['addonAA']['apiConfiguration']['item']['tech_region']['label']                                  = 'Bundesland/Region';

$_LANG['addonAA']['apiConfiguration']['item']['testConnection']['content'] = 'Verbindung testen';
$_LANG['addonAA']['apiConfiguration']['item']['saveItem']['label']         = 'Speichern';
$_LANG['addonAA']['pagesLabels']['label']['productsConfiguration']         = 'Products Configuration';
$_LANG['addonAA']['pagesLabels']['label']['productsCreator']               = 'Products Creator';
$_LANG['addonAA']['pagesLabels']['apiConfiguration']['saveItem']           = 'Speichern';

$_LANG['addonAA']['apiConfiguration']['messages']['api_connection_success'] = 'Verbindung hergestellt.';


$_LANG['addonAA']['productsConfiguration']['realtimeRegisterSSLProduct']    = 'RealtimeRegisterSSL-Produkt:';
$_LANG['addonAA']['productsConfiguration']['productName']         = 'Produktname:';
$_LANG['addonAA']['productsConfiguration']['customguide']         = 'Processing SSL message:';
$_LANG['addonAA']['productsConfiguration']['configurableOptions'] = 'Konfigurierbare Optionen SAN:';
$_LANG['addonAA']['productsConfiguration']['configurableOptionsWildcard'] = 'Konfigurierbare Optionen Wildcard SAN:';
$_LANG['addonAA']['productsConfiguration']['configurableOptionsPeriod'] = 'Konfigurierbare Optionen Period:';
$_LANG['addonAA']['productsConfiguration']['createConfOptions']   = 'Generieren';
$_LANG['addonAA']['productsConfiguration']['editPrices']          = 'Preise ändern';
$_LANG['addonAA']['productsConfiguration']['autoSetup']           = 'Automatische Einrichtung:';
$_LANG['addonAA']['productsConfiguration']['autoSetupOrder']      = 'Das Produkt automatisch einrichten, sobald eine Bestellung aufgegeben wird';
$_LANG['addonAA']['productsConfiguration']['autoSetupPayment']    = 'Das Produkt automatisch einrichten, sobald die erste Zahlung eingegangen ist';
$_LANG['addonAA']['productsConfiguration']['autoSetupOn']         = 'Das Produkt automatisch einrichten, wenn Sie eine ausstehende Bestellung manuell akzeptieren';
$_LANG['addonAA']['productsConfiguration']['autoSetupOff']        = 'Dieses Produkt nicht automatisch einrichten';
$_LANG['addonAA']['productsConfiguration']['months']              = 'Maximale Monate:';
$_LANG['addonAA']['productsConfiguration']['enableSans']          = 'SANs aktivieren:';
$_LANG['addonAA']['productsConfiguration']['enableSansWildcard']  = 'SANs Wildcard aktivieren:s:';
$_LANG['addonAA']['productsConfiguration']['includedSans']        = 'Enthaltene SANs:';
$_LANG['addonAA']['productsConfiguration']['includedSansWildcard']= 'Included Wildcard SANs:';
$_LANG['addonAA']['productsConfiguration']['status']              = 'Status:';
$_LANG['addonAA']['productsConfiguration']['setForManyProducts']  = 'Für mehrere Produkte einstellen';
$_LANG['addonAA']['productsConfiguration']['statusEnabled']       = 'Status aktiviert:';
$_LANG['addonAA']['productsConfiguration']['allOrSelectedProducts'] = 'Alle oder ausgewählte Produkte:';
$_LANG['addonAA']['productsConfiguration']['selectProducts']      = 'Produkte auswählen:';
$_LANG['addonAA']['productsConfiguration']['allProducts']         = 'Alle Produkte';
$_LANG['addonAA']['productsConfiguration']['selectedProducts']    = 'Ausgewählte Produkte';
$_LANG['addonAA']['productsConfiguration']['areYouSureManyProducts'] = 'Möchten Sie diese Einstellungen wirklich für mehrere Produkte verwenden?';
$_LANG['addonAA']['productsConfiguration']['doNotAnything']       = 'Abbrechen';

$_LANG['addonAA']['productsConfiguration']['statusEnable']  = 'Aktivieren';
$_LANG['addonAA']['productsConfiguration']['statusDisable'] = 'Deaktivieren';


$_LANG['addonAA']['productsConfiguration']['paymentType']          = 'Zahlungsart:';
$_LANG['addonAA']['productsConfiguration']['priceAutoDownlaod']    = 'Preise automatisch holen:';
$_LANG['addonAA']['productsConfiguration']['commission']           = 'Kommission[%]:';
$_LANG['addonAA']['productsConfiguration']['paymentTypeFree']      = 'Kostenlos';
$_LANG['addonAA']['productsConfiguration']['paymentTypeRecurring'] = 'Wiederkehrend';
$_LANG['addonAA']['productsConfiguration']['paymentTypeOneTime']   = 'Einmalig';

$_LANG['addonAA']['productsConfiguration']['pricing']             = 'Preisgestaltung:';
$_LANG['addonAA']['productsConfiguration']['pricingMonthly']      = 'Einmalig';
$_LANG['addonAA']['productsConfiguration']['pricingQuarterly']    = 'Vierteljährlich';
$_LANG['addonAA']['productsConfiguration']['pricingSemiAnnually'] = 'Halbjährlich';
$_LANG['addonAA']['productsConfiguration']['pricingAnnually']     = 'Jährlich';
$_LANG['addonAA']['productsConfiguration']['pricingBiennially']   = 'Zweijährlich';
$_LANG['addonAA']['productsConfiguration']['pricingTriennially']  = 'Dreijährlich';

$_LANG['addonAA']['productsConfiguration']['pricingSetupFee']        = 'Einrichtungsgebühr';
$_LANG['addonAA']['productsConfiguration']['pricingPrice']           = 'Preis';
$_LANG['addonAA']['productsConfiguration']['pricingCommissionPrice'] = 'Preis mit Kommission';
$_LANG['addonAA']['productsConfiguration']['pricingEnable']          = 'Aktivieren';

$_LANG['addonAA']['productsConfiguration']['save']         = 'Speichern';
$_LANG['addonAA']['productsConfiguration']['messages'][''] = '';


$_LANG['addonAA']['productsCreator']['singleProductCreator'] = 'Einzelprodukt-Ersteller';
$_LANG['addonAA']['productsCreator']['realtimeRegisterSSLProduct']     = 'RealtimeRegisterSSL-Produkt:';
$_LANG['addonAA']['productsCreator']['productName']          = 'Produktname:';
$_LANG['addonAA']['productsCreator']['customguide']          = 'Processing SSL message:';
$_LANG['addonAA']['productsCreator']['productGroup']         = 'Produktgruppe:';
$_LANG['addonAA']['productsCreator']['autoSetup']            = 'Automatische Einrichtung:';
$_LANG['addonAA']['productsCreator']['autoSetupOrder']       = 'Das Produkt automatisch einrichten, sobald eine Bestellung aufgegeben wird';
$_LANG['addonAA']['productsCreator']['autoSetupPayment']     = 'Das Produkt automatisch einrichten, sobald die erste Zahlung eingegangen ist';
$_LANG['addonAA']['productsCreator']['autoSetupOn']          = 'Das Produkt automatisch einrichten, wenn Sie eine ausstehende Bestellung manuell akzeptieren';
$_LANG['addonAA']['productsCreator']['autoSetupOff']         = 'Dieses Produkt nicht automatisch einrichten';
$_LANG['addonAA']['productsCreator']['months']               = ' Monate:';



$_LANG['addonAA']['productsCreator']['enableSans']   = 'SANs aktivieren:';
$_LANG['addonAA']['productsCreator']['includedSans'] = 'Enthaltene SANs:';

$_LANG['addonAA']['productsCreator']['pricing']             = 'Preisgestaltung:';
$_LANG['addonAA']['productsCreator']['pricingMonthly']      = 'Einmalig';
$_LANG['addonAA']['productsCreator']['pricingQuarterly']    = 'Vierteljährlich';
$_LANG['addonAA']['productsCreator']['pricingSemiAnnually'] = 'Halbjährlich';
$_LANG['addonAA']['productsCreator']['pricingAnnually']     = 'Jährlich';
$_LANG['addonAA']['productsCreator']['pricingBiennially']   = 'Zweijährlich';
$_LANG['addonAA']['productsCreator']['pricingTriennially']  = 'Dreijährlich';

$_LANG['addonAA']['productsCreator']['pricingSetupFee'] = 'Einrichtungsgebühr';
$_LANG['addonAA']['productsCreator']['pricingPrice']    = 'Preis';
$_LANG['addonAA']['productsCreator']['pricingEnable']   = 'Aktivieren';
$_LANG['addonAA']['productsCreator']['saveSingle']      = 'Einzelprodukt erstellen';

$_LANG['addonAA']['productsCreator']['multipleProductCreator'] = 'Mehrfacher-Produkt-Ersteller';
$_LANG['addonAA']['productsCreator']['saveMultiple']           = 'Mehrere Produkte erstellen';

$_LANG['addonAA']['productsCreator']['messages']['mass_product_created']    = 'Produkte wurden als ausgeblendet hinzugefügt. Gehen Sie zu "Produktkonfiguration", um sie einzublenden. Überprüfen Sie zuvor die Produktkonfiguration und legen Sie die Preise fest.';
$_LANG['addonAA']['productsCreator']['messages']['single_product_created']  = 'Das Produkt wurde als ausgeblendet hinzugefügt. Gehen Sie zu "Produktkonfiguration", um es einzublenden. Überprüfen Sie zuvor die Produktkonfiguration.';
$_LANG['addonAA']['productsCreator']['messages']['no_product_group_found']  = 'Keine Produktgruppe gefunden.';
$_LANG['addonAA']['productsCreator']['messages']['api_product_not_chosen']  = 'RealtimeRegisterSSL-Produkt nicht ausgewählt.';
$_LANG['addonAA']['productsCreator']['messages']['api_configuration_empty'] = 'Die API-Konfiguration ist leer';

$_LANG['addonAA']['productsConfiguration']['messages']['product_saved']          = 'Produkt gespeichert.';
$_LANG['addonAA']['productsConfiguration']['messages']['configurable_generated'] = 'Konfigurierbare Optionen für das Produkt wurden erfolgreich generiert.';

$_LANG['addonAA']['productsConfiguration']['messages']['api_configuration_empty'] = 'Die API-Konfiguration ist leer';

$_LANG['addonAA']['userCommissions']['integrationCode']['header']         = 'Integrationscode';
$_LANG['addonAA']['userCommissions']['pleaseNote']                        = 'Bitte beachten';
$_LANG['addonAA']['userCommissions']['info']                              = 'Um die Produktpreise mit zusätzliche Kommission im Kundenbereich anzuzeigen: ';
$_LANG['addonAA']['userCommissions']['info1']                             = '1. Datei öffnen';
$_LANG['addonAA']['userCommissions']['info2']                             = '2. Fügen Sie diesen Code oben in die Datei ein';
$_LANG['addonAA']['userCommissions']['info3']                             = '3. Datei öffnen';
$_LANG['addonAA']['userCommissions']['info4']                             = '4. Fügen Sie diesen Code oben in die Datei ein';
$_LANG['addonAA']['pagesLabels']['label']['userCommissions']              = 'Kommissionsregeln';
$_LANG['addonAA']['userCommissions']['title']                             = 'Kommissionsregeln';
$_LANG['addonAA']['userCommissions']['addNewCommissionRule']              = 'Kommissionsregeln';
$_LANG['addonAA']['userCommissions']['editItem']                          = 'Bearbeiten';
$_LANG['addonAA']['userCommissions']['deleteItem']                        = 'Entfernen';
$_LANG['addonAA']['userCommissions']['messages']['addSuccess']            = 'Kommissionsregel erfolgreich hinzugefügt.';
$_LANG['addonAA']['userCommissions']['messages']['removeSuccess']         = 'Kommissionsregel erfolgreich entfernt.';
$_LANG['addonAA']['userCommissions']['messages']['updateSuccess']         = 'Kommissionsregel erfolgreich aktualisiert.';
$_LANG['addonAA']['userCommissions']['messages']['clientIDNotProvided']   = 'Kunden-ID wurde nicht angegeben.';
$_LANG['addonAA']['userCommissions']['messages']['ruleIDNotProvided']     = 'Regel-ID wurde nicht angegeben.';
$_LANG['addonAA']['userCommissions']['messages']['productIDNotProvided']  = 'Produkt-ID wurde nicht angegeben.';
$_LANG['addonAA']['userCommissions']['messages']['commissionNotProvided'] = 'Kommission wurde nicht angegeben.';

$_LANG['addonAA']['userCommissions']['table']['client']                       = 'Kunde';
$_LANG['addonAA']['userCommissions']['table']['product']                      = 'Produkt';
$_LANG['addonAA']['userCommissions']['table']['commission']                   = 'Kommission[%]';
$_LANG['addonAA']['userCommissions']['table']['monthly/onetime']              = 'Einmalig/Monatlich';
$_LANG['addonAA']['userCommissions']['table']['quarterly']                    = 'Vierteljährlich';
$_LANG['addonAA']['userCommissions']['table']['semiannually']                 = 'Halbjährlich';
$_LANG['addonAA']['userCommissions']['table']['annually']                     = 'Jährlich';
$_LANG['addonAA']['userCommissions']['table']['biennially']                   = 'Zweijährlich';
$_LANG['addonAA']['userCommissions']['table']['triennially']                  = 'Dreijährlich';
$_LANG['addonAA']['userCommissions']['table']['actions']                      = 'Aktionen';
$_LANG['addonAA']['userCommissions']['modal']['pleaseSelecetOnePlaceholder']  = 'Bitte wählen Sie aus...';
$_LANG['addonAA']['userCommissions']['modal']['selectClientFirstPlaceholder'] = 'Bitte wählen Sie zuerst einen Kunden aus...';
$_LANG['addonAA']['userCommissions']['modal']['pleaseSelectProductFirst']     = 'Bitte wählen Sie zuerst ein Produkt aus...';
$_LANG['addonAA']['userCommissions']['modal']['noDataAvailable']              = 'Keine Daten verfügbar.';
$_LANG['addonAA']['userCommissions']['modal']['noClientAvailable']            = 'Keine Kunden verfügbar.';
$_LANG['addonAA']['userCommissions']['modal']['noProductAvailable']           = 'Keine Produkte verfügbar.';
$_LANG['addonAA']['userCommissions']['table']['basePrice']                    = 'Grundpreis: ';
$_LANG['addonAA']['userCommissions']['table']['priceWithCommission']          = 'Mit Kommission: ';

$_LANG['addonAA']['userCommissions']['modal']['addCommissionRule']          = 'Neue Kommissionsregel hinzufügen';
$_LANG['addonAA']['userCommissions']['modal']['client']                     = 'Kunde';
$_LANG['addonAA']['userCommissions']['modal']['product']                    = 'Produkt';
$_LANG['addonAA']['userCommissions']['modal']['commission']                 = 'Kommission[%]';
$_LANG['addonAA']['userCommissions']['modal']['add']                        = 'Hinzufügen';
$_LANG['addonAA']['userCommissions']['modal']['edit']                       = 'Änderungen speichern';
$_LANG['addonAA']['userCommissions']['modal']['close']                      = 'Schlie&szlig;en';
$_LANG['addonAA']['userCommissions']['modal']['productPrice']               = 'Produktpreis';
$_LANG['addonAA']['userCommissions']['modal']['productPriceWithCommission'] = 'Produktpreis mit Kommission';
$_LANG['addonAA']['userCommissions']['modal']['monthly/onetime']            = 'Einmalig/Monatlich';
$_LANG['addonAA']['userCommissions']['modal']['quarterly']                  = 'Vierteljährlich';
$_LANG['addonAA']['userCommissions']['modal']['semiannually']               = 'Zweijährlich';
$_LANG['addonAA']['userCommissions']['modal']['annually']                   = 'Jährlich';
$_LANG['addonAA']['userCommissions']['modal']['biennially']                 = 'Zweijährlich';
$_LANG['addonAA']['userCommissions']['modal']['triennially']                = 'Dreijährlich';
$_LANG['addonAA']['userCommissions']['modal']['removeRule']                 = 'Kommissionsregel entfernen';
$_LANG['addonAA']['userCommissions']['modal']['remove']                     = 'Entfernen';
$_LANG['addonAA']['userCommissions']['modal']['removeRuleInfo']             = 'Sie sind dabei, die Kommissionsregel zu entfernen. Dieser Vorgang ist irreversibel.';


$_LANG['anErrorOccurred'] = 'Ein Fehler ist aufgetreten';

$_LANG['pagesLabels']['label']['orders']       = 'SSL-Bestellung importieren';
$_LANG['addonCA']['sslSummary']['title']       = 'Zusammenfassung der SSL-Bestellungen';
$_LANG['addonCA']['sslSummary']['total']       = 'Bestellungen insgesamt';
$_LANG['addonCA']['sslSummary']['unpaid']      = 'Unbezahlte Bestellungen';
$_LANG['addonCA']['sslSummary']['processing']  = 'In Bearbeitung';
$_LANG['addonCA']['sslSummary']['expiresSoon'] = 'Läuft bald ab';

$_LANG['addonCA']['sslSummaryOrdersPage']['pageTitle']['total']        = 'Bestellungen insgesamt';
$_LANG['addonCA']['sslSummaryOrdersPage']['pageTitle']['unpaid']       = 'Unbezahlte Bestellungen';
$_LANG['addonCA']['sslSummaryOrdersPage']['pageTitle']['processing']   = 'Bestellungen in Bearbeitung';
$_LANG['addonCA']['sslSummaryOrdersPage']['pageTitle']['expires_soon'] = 'Bald ablaufende Bestellungen';
$_LANG['addonCA']['sslSummaryOrdersPage']['Product/Service']           = 'Produkt/Dienstleistung';
$_LANG['addonCA']['sslSummaryOrdersPage']['Pricing']                   = 'Preisgestaltung';
$_LANG['addonCA']['sslSummaryOrdersPage']['Next Due Date']             = 'Nächstes Fälligkeitsdatum';
$_LANG['addonCA']['sslSummaryOrdersPage']['Status']                    = 'Status';

$_LANG['invalidEmailAddress']           = 'E-Mail-Adresse ist falsch';
$_LANG['csrCodeGeneratedSuccessfully'] = 'CSR-Code wurde erfolgreich generiert';
$_LANG['invalidCountryCode']            = 'Ländercode ist falsch';
$_LANG['csrCodeGeneraterFailed']        = 'Das Generieren des CSR-Codes ist fehlgeschlagen';

$_LANG['addonAA']['productsConfiguration']['save_all_products']       = 'Save all products';
$_LANG['addonAA']['productsConfiguration']['products_saved']       = 'Products has been saved.';

$_LANG['viewAll']		= 'View All';

$_LANG['additionalSingleDomainInfo'] = 'This product has %s additional single domain SAN\'s included.';
$_LANG['additionalSingleDomainWildcardInfo'] = 'This product has %s additional wildcard domain SAN\'s included.';

$_LANG['addonAA']['userCommissions']['modal']['noClientAvailablePlaceholder'] = 'No Client Avaialble Placeholder';
$_LANG['addonAA']['userCommissions']['modal']['noProductAvailablePlaceholder'] = 'No Product Avaialble Placeholder';
$_LANG['addonAA']['userCommissions']['modal']['editCommissionRule'] = 'Edit Commission Rule';
$_LANG['addonAA']['pagesLabels']['label']['logs'] = 'Logs';

$_LANG['addonAA']['logs']['title'] = 'Logs';
$_LANG['addonAA']['logs']['table']['id'] = 'Id';
$_LANG['addonAA']['logs']['table']['client'] = 'Client';
$_LANG['addonAA']['logs']['table']['service'] = 'Service';
$_LANG['addonAA']['logs']['table']['type'] = 'Type';
$_LANG['addonAA']['logs']['table']['msg'] = 'Msg';
$_LANG['addonAA']['logs']['table']['date'] = 'Date';
$_LANG['addonAA']['logs']['table']['actions'] = 'Actions';
$_LANG['addonAA']['logs']['deleteItem'] = 'Delete Log';
$_LANG['addonAA']['pagesLabels']['label']['orders'] = 'Orders';
$_LANG['addonAA']['logs']['modal']['removeLog'] = 'Remove Log';
$_LANG['addonAA']['logs']['modal']['removeLogInfo'] = 'Are you sure remove this log?';
$_LANG['addonAA']['logs']['modal']['remove'] = 'Remove';
$_LANG['addonAA']['logs']['modal']['close'] = 'Close';
$_LANG['addonAA']['logs']['messages']['logIDNotProvided'] = 'Log ID Not Provided';
$_LANG['addonAA']['logs']['button']['clear_logs'] = 'Clear logs';
$_LANG['addonAA']['logs']['modal']['clearLogs'] = 'Clear Logs';
$_LANG['addonAA']['logs']['modal']['clearLogsInfo'] = 'Are you sure you want to clear all logs?';
$_LANG['addonAA']['logs']['modal']['Clear'] = 'Clear';
$_LANG['addonAA']['logs']['messages']['clearSuccess'] = 'All logs have been cleared';
$_LANG['addonAA']['logs']['messages']['removeSuccess'] = 'The log has been successfully deleted';

$_LANG['Reissue Certificate'] = 'Reissue Certificate';
$_LANG['serverCA']['home']['Configuration Submitted'] = 'Configuration Submitted';
$_LANG['serverCA']['home']['anErrorOccurred'] = 'An Error Occurred';

$_LANG['addonAA']['orders']['title'] = 'Orders';
$_LANG['addonAA']['orders']['table']['id'] = 'Id';
$_LANG['addonAA']['orders']['table']['client'] = 'Client';
$_LANG['addonAA']['orders']['table']['service'] = 'Service';
$_LANG['addonAA']['orders']['table']['order'] = 'SSL Order';
$_LANG['addonAA']['orders']['table']['verification_method'] = 'Verification method';
$_LANG['addonAA']['orders']['table']['status'] = 'Status';
$_LANG['addonAA']['orders']['table']['date'] = 'Date';
$_LANG['addonAA']['orders']['table']['actions'] = 'Actions';

$_LANG['Choose a domain'] = 'Choose a domain';

$_LANG['addonAA']['crons']['cron_certificate_installer'] = 'Installieren Sie das Zertifikat in Ihrem Hostingpanel (einmal täglich)';

$_LANG['addonAA']['orders']['table']['set as verified'] = 'Set as verified';
$_LANG['addonAA']['orders']['table']['set as installed'] = 'Set as installed';
$_LANG['addonAA']['orders']['messages']['Success'] = 'Success';

$_LANG['haveCsr'] = 'Ich habe eine CSR, die ich verwenden möchte';
$_LANG['createCsr'] = 'Helfen Sie mir beim Erstellen einer CSR';