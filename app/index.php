<?php

$root = dirname(dirname(__FILE__));
require_once $root.'/src/config.php';
require_once $root.'/src/mysqli.php';
require_once $root.'/src/utils.php';

$timezone = new DateTimeZone('Europe/Berlin');

?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Covid-19-Impfungen: Terminportal</title>

  <link rel="stylesheet" href="./css/main.css">
  <script src="./js/main.js" defer></script>
</head>
<body>

  <header>
    <div class="black-line">
      <img src="./images/logo.svg" />
      <a id="cancel" class="button button-cancel" data-ajax="1" data-dialog="dialog-details">Buchung ansehen</a>
    </div>
    <div>
      <h1>Covid-19-Impfungen: Terminportal</h1>
    </div>
  </header>

  <div class="card">
    <snap-tabs>
      <header class="scroll-snap-x"> 
        <nav>
          <?php

          $is_active = true;

          $sql = sprintf(
            'SELECT epochDate
              FROM locations_epoch
              ORDER BY epochDate ASC');
          $ret = $conn->query($sql);

          $data = array();    
          while ($row = $ret->fetch_assoc()) {
            $starttime = new DateTime($row['epochDate']);
            $starttime->setTimezone($timezone);

            echo tag(
              'a',
              array(
                'data-date' => $starttime->format('Ymd'),
                'active' => $is_active,
              ),
              formatDate($starttime));

            if ($is_active) {
              $is_active = false;
            }

            $starttime->modify('+1 day');
          }
          
          ?>
        </nav>
      </header>
      <section>
        <article class="scroll-snap-x"></article>
      </section>
    </snap-tabs> 
  </div>

  <div class="dialog-container" id="dialog-booking">
    <div class="dialog">
      <button id="btnDialogClose" class="button button-close">&times;</button>

      <div class="dialog-step">
        <div class="dialog-title">Terminvereinbarung starten</div>
        <div class="dialog-body">M??chten Sie diesen Impftermin wirklich buchen? Ihr Impftermin ist nicht ??bertragbar und die Terminvereinbarung ist ausschlie??lich f??r Angestellte der BVG erlaubt.</div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Kein Zutritt f??r Begleitpersonen</div>
        <div class="dialog-body">Beachten Sie, dass das Impfzentrum nicht von Begleitpersonen oder Kindern betreten werden kann. Im Impfzentrum steht Unterst??tzung f??r Sie bereit, falls Sie diese ben??tigen.</div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Unter 18 Jahren?</div>
        <div class="dialog-body">In diesem Fall ist eine schriftliche Erlaubnis zur Impfung vom Erziehungsberechtigten zur Impfung mitzubringen.</div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">In den letzten 6 Monaten an COVID-19 erkrankt?</div>
        <div class="dialog-body">Eine Impfung sollte fr??hestens 6 Monate nach einer vorangegangen Erkrankung an COVID-19 durchgef??hrt werden. Bringen Sie dazu das Ergebnis des PCR-Tests mit dem positiven Befund mit.</div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Erstimpfung COVID-19</div>
        <div class="dialog-body">In diesem Impfzentrum wird der Impfstoff BioNTech-Pfizer verimpft.</div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Notwendige Dokumente</div>
        <div class="dialog-body">
          <p>Bitte bringen Sie die nachfolgenden Dokumente ausgef??llt und unterschrieben zu Ihrem Termin mit:</p>
          <ul>
            <li><a href="https://www.rki.de/DE/Content/Infekt/Impfen/Materialien/COVID-19-Aufklaerungsbogen-Tab.html" target="_blank">Aufkl??rungsmerkblatt</a></li>
            <li><a href="https://www.rki.de/DE/Content/Infekt/Impfen/Materialien/COVID-19-Aufklaerungsbogen-Tab.html" target="_blank">Anamnese- und Einwilligungsbogen</a></li>
          </ul>
        </div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Notwendige Dokumente</div>
        <div class="dialog-body">
          <p>Au??erdem ist folgendes mitzuf??hren:</p>
          <ul>
            <li>offizielles Ausweisdokument</li>
            <li>Dienstausweis</li>
            <li>Impfpass</li>
            <li>falls vorhanden: Medikamentenausweise</li>
          </ul>
        </div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Pers??nliche Angaben</div>
        <div class="dialog-body">
          <?php
            echo booking_form();
          ?>
        </div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Datenschutz</div>
        <div class="dialog-body">
          Ich habe die <a href="./documents/20210604_Datenschutzerklaerung.pdf" target="_blank">Datenschutzerkl??rung</a> gelesen und akzeptiere diese.
        </div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Zusammenfassung</div>
        <div class="dialog-body">
          <div class="dialog-form">
            <div class="fieldset-item">
              <div class="input-stack">
                <h3>Datum</h3>
                <p id="summary-date"></p>
              </div>
              <div class="input-stack">
                <h3>Uhrzeit</h3>
                <p id="summary-time"></p>
              </div>
              <div class="input-stack">
                <h3>Ort</h3>
                <p id="summary-location"></p>
                <p id="summary-address" style="white-space: pre"></p>
              </div>
              <div class="input-stack">
                <h3>Buchungscode</h3>
                <p id="summary-code"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="dialog-buttons">
        <button id="btnDialogPrev" class="button">Zur??ck</button>
        <button id="btnDialogNext" class="button button-colored">Weiter</button>
      </div>
    </div>
  </div>

  <div class="dialog-container" id="dialog-details">
    <div class="dialog">
      <button id="btnDialogClose" class="button button-close">&times;</button>

      <div class="dialog-step">
        <div class="dialog-title">Terminbuchung anzeigen</div>
        <div class="dialog-body">
          <form class="dialog-form" method="POST">
            <div class="fieldset-item repeat-2">
              <div class="input-stack">
                <label for="danumber">DA-Nummer</label>
                <input id="danumber" name="danumber" type="text" maxlength="6" pattern="[A-Za-z????????????0]{0,1}[0-9]{1,5}" inputmode="text" required="1">
              </div>
              <div class="input-stack">
                <label for="code">Buchungscode</label>
                <input id="code" name="code" type="text" maxlength="8" pattern="[A-Za-z0-9]{8}" inputmode="text" required="1">
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Vereinbarter Termin zur Impfung</div>
        <div class="dialog-body">
          <div class="dialog-form">
            <div class="fieldset-item repeat-2">
              <div class="input-stack">
                <h3>Vorname</h3>
                <p id="details-firstname"></p>
              </div>
              <div class="input-stack">
                <h3>Nachname</h3>
                <p id="details-lastname"></p>
              </div>
            </div>
            <div class="fieldset-item repeat-2">
              <div class="input-stack">
                <h3>E-Mail</h3>
                <p id="details-email"></p>
              </div>
              <div class="input-stack">
                <h3>Telefon</h3>
                <p id="details-phone"></p>
              </div>
            </div>
            <div class="fieldset-item repeat-2">
              <div class="input-stack">
                <h3>Datum</h3>
                <p id="details-date"></p>
              </div>
              <div class="input-stack">
                <h3>Uhrzeit</h3>
                <p id="details-time"></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="dialog-step">
        <div class="dialog-title">Buchung storniert</div>
        <div class="dialog-body">
          Ihre Buchung wurde erfolgreich storniert.
        </div>
      </div>
      
      <div class="dialog-buttons">
        <button id="btnDialogPrev" class="button">Zur??ck</button>
        <button id="btnDialogNext" class="button button-colored">Weiter</button>
      </div>

    </div>
  </div>

  <footer>
    <div>2021 &copy; Berliner Verkehrsbetriebe</div>
    <div>
      <a href="https://www.bvg.de/de/Serviceseiten/Impressum">Impressum</a>
      <a href="https://www.bvg.de/de/Serviceseiten/Datenschutzhinweise">Datenschutz</a>
    </div>
  </footer>
</body>
</html>