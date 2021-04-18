# phpbb_messageimport

Dieses Addon ist dazu gedacht aus xml-Exports einer anderen Forensoftware neue Foren zu generieren. Dabei werden gleichzeitig die notwendigen alten topid-ids in neue umgesetzt. Dadurch kann eine Konvertierung der eventuell vorhandenen Links einer alten Forensoftware übernommen werden.

Außerdem werden eventuell vorhandene Benutzer mit den Artikeln die geschrieben wurden verknüpft. Dadurch ist eine Migration, sofern die Benutzernamen übereinstimmen, auch mit den gleichen Nutzern möglich.

## Installation

Das Verzeichnis febrildur und deren Inhalt wird in das Verzeichnis ext der vorhandenen phpbb-Installation kopiert. Danach über den Administrationsbereich installiert.

Die zu importierenden xml-Dateien müssen in das Verzeichnis "store" der phpbb-Umgebung hochgeladen werden und können von dort aus über die Seite "Wartung" eingespielt werden. Achtung, es wird hier nicht überprüft ob Topics eventuell schon vorhanden sind. Falls man dies mehrfach auslöst, werden diese auch mehrfach importiert.