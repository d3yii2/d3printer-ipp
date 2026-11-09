# d3yii2/d3printeripp — IPP printeru vadības modulis

Yii2 (PHP 7.4) modulis tīkla printeru vadībai, izmantojot **IPP (Internet Printing Protocol)**.
Testēts ar **HP LaserJet Pro 3002dn**.

Modulis nodrošina:

- dokumentu drukāšanu tieši uz printeri (IPP `Print-Job`);
- drukas rindu (spool) failu sistēmā un fona procesu (daemon), kas šo rindu izdrukā;
- printera stāvokļa nolasīšanu (statuss, kļūdu iemesli, papīra padeve, tonera līmenis);
- konfigurējamus brīdinājumu/kļūdu noteikumus (rules) un e-pasta paziņojumus, kad stāvoklis mainās;
- statusa kešošanu un paneli (dashboard) ar printera stāvokli, testa izdrukas un atjaunošanas pogām.

IPP komunikācijai izmantota bibliotēka `obray/ipp` (fork: `https://github.com/DBRisinajumi/IPP.git`).

---

## 1. Arhitektūra

```
                 ┌──────────────────────┐
  aplikācija ───►│ BasePrinter          │──printFile()/printContent()──► printeris (ipp://host:631)
                 │ (komponente katram   │
                 │  printerim)          │──printToSpoolDirectory()──┐
                 └──────────┬───────────┘                           ▼
                            │                           ┌──────────────────────┐
                            │                           │ Spooler              │
                            │                           │ runtime/<base>/spool_│
                            │                           │ <printerName>/*.pdf  │
                            │                           └──────────┬───────────┘
                            │                                      │
                            │                     php yii printeripp-spool/index
                            │                           ┌──────────▼───────────┐
                            │                           │ PrintSpooler (daemon)│──► printeris
                            │                           └──────────────────────┘
          getStatusFromPrinter()
                            ▼
                 ┌──────────────────────┐      ┌──────────────┐     ┌────────┐
                 │ AlertConfig + rules  │─────►│ PrinterCache │◄────│ panelis│
                 └──────────┬───────────┘      └──────────────┘     └────────┘
                            │ stāvoklis mainījies
                            ▼
                 ┌──────────────────────┐
                 │ Mailer → e-pasts     │
                 └──────────────────────┘
```

| Klase | Loma |
|---|---|
| `components\BasePrinter` | Viens konkrēts printeris. Drukāšana, statusa iegūšana, spool, e-pasts. |
| `components\Spooler` | Drukas rinda: kopē failus uz `runtime/<baseDirectory>/spool_<printerName>/`. |
| `components\PrintSpooler` | Daemon loģika: iet cauri printeriem, drukā rindas failus, pēc veiksmīgas drukas tos dzēš. |
| `components\AlertConfig` | Abstrakta klase; projektā jāizveido mantotā klase ar `rules()`. |
| `components\rules\*` | Atsevišķi stāvokļa noteikumi (statuss, toneris, padeve u.c.). |
| `components\PrinterCache` | Pēdējā stāvokļa kešs (`FileCache` iekš `runtime/ippPrinterCache`). |
| `components\Mailer` | Brīdinājuma e-pasta sūtīšana caur `Yii::$app->mailer`. |
| `commands\PrinterCommand` | Konsoles komandas: statuss, atribūti, testa druka. |
| `commands\SpoolerCommand` | Konsoles daemon drukas rindas apstrādei. |
| `controllers\PrinterPanelController` | Dashboard panelis. |
| `controllers\PrinterController` | Testa izdruka un statusa atjaunošana no paneļa. |

> Mape `logic/` (`PrinterAttributes`, `PrinterSupplies`, `printers\HPPrinter` u.c.) ir vecā implementācija — tā atsaucas uz neesošu klasi `logic\BasePrinter` un netiek izmantota. Lietot `components\BasePrinter`.

---

## 2. Instalācija

`composer.json` pievieno IPP bibliotēkas repozitoriju:

```json
"repositories": [
    { "type": "git", "url": "https://github.com/DBRisinajumi/IPP.git" }
]
```

```bash
composer require d3yii2/d3printeripp
```

Prasības: PHP ≥ 7.4, `ext-curl`, `yiisoft/yii2 ~2.0.14`, `d3yii2/d3system`, `cornernote/yii2-returnurl`, `unyii2/yii2-panel`, `obray/ipp`.

### Printera sagatavošana (HP LaserJet Pro 3002dn)

1. Printerim piešķirt fiksētu IP adresi (DHCP rezervācija vai statiska IP).
2. Printera tīmekļa saskarnē (EWS, `https://<ip>`) pārliecināties, ka ir ieslēgts **IPP** (Networking → Protocols / Print Services).
3. IPP ports: `631`. URI, ko izmanto modulis: `ipp://<host>:<port>`.
4. Ja EWS ir aizsargāts ar paroli, norādīt `username` (parasti `admin`) un `password`.
5. Pārbaudīt savienojamību no servera: `php yii printeripp/attributes <komponente>`.

---

## 3. Konfigurācija

### 3.1 Konsoles konfigurācija (`config/console.php`)

```php
'controllerMap' => [
    'printeripp'       => 'd3yii2\d3printeripp\commands\PrinterCommand',
    'printeripp-spool' => 'd3yii2\d3printeripp\commands\SpoolerCommand',
],
```

### 3.2 Web konfigurācija — modulis

```php
'modules' => [
    'd3printeripp' => [
        'class' => 'd3yii2\d3printeripp\Module',
        // lomas, kas drīkst redzēt paneli, drukāt testa lapu un atjaunot statusu
        'panelViewRoleNames' => ['D3PrinterViewPanel'],
    ],
],
```

### 3.3 Komponentes (gan web, gan console)

Servisa komponentes (`printerSpooler`, `ippAlertConfig`, `ippPrinterMailer`, `printerStatusCache`, `spoolerPrinting`) var būt kopīgas vairākiem printeriem. Katram printerim veido savu `BasePrinter` komponenti.

```php
'components' => [

    // Drukas rinda (faili runtime/d3printer/spool_<printerName>/)
    'printerSpooler' => [
        'class' => 'd3yii2\d3printeripp\components\Spooler',
        'baseDirectory' => 'd3printer',
    ],

    // Brīdinājumu noteikumi — projekta klase, sk. 5. nodaļu
    'ippAlertConfig' => [
        'class' => 'app\components\IppPrinter3002dnAlertConfig',
    ],

    // Brīdinājuma e-pasti
    'ippPrinterMailer' => [
        'class' => 'd3yii2\d3printeripp\components\Mailer',
        'from' => 'system@example.lv',
        'to' => ['admin@example.lv'],
        // pēc noklusējuma: 'System "{systemName}",Problems with the "{name}" {deviceName}'
        // 'subject' => '...',
    ],

    // Statusa kešs paneļa vajadzībām
    'printerStatusCache' => [
        'class' => 'd3yii2\d3printeripp\components\PrinterCache',
        // 'cachePath' => 'ippPrinterCache',  // relatīvi pret runtime
        // 'cacheDuration' => 60,             // sekundes
    ],

    // Drukas rindas daemon komponente
    'spoolerPrinting' => [
        'class' => 'd3yii2\d3printeripp\components\PrintSpooler',
        'printersComponentNames' => ['printerOffice'],
    ],

    // Printeris
    'printerOffice' => [
        'class' => 'd3yii2\d3printeripp\components\BasePrinter',
        'printerName' => 'printerOffice',     // izmanto spool mapes un keša atslēgai
        'name' => 'Biroja HP 3002dn',         // cilvēkam lasāms nosaukums (panelis, e-pasts)
        'host' => '192.168.88.168',
        'port' => 631,
        'username' => 'admin',
        'password' => '',
        'spoolerComponentName' => 'printerSpooler',
        'alertConfigComponentName' => 'ippAlertConfig',
        'mailerComponentName' => 'ippPrinterMailer',
        'cacheComponentName' => 'printerStatusCache',
        'pageOrientation' => \d3yii2\d3printeripp\types\PrinterAttributeValues::ORIENTATION_PORTRAIT, // 3
        'pageSize' => \d3yii2\d3printeripp\types\PrinterAttributeValues::MEDIA_SIZE_A4, // 'iso_a4_210x297mm'
        // 'curlOptions' => [CURLOPT_TIMEOUT => 20],
    ],
],
```

#### `BasePrinter` īpašības

| Īpašība | Tips | Noklusējums | Apraksts |
|---|---|---|---|
| `printerName` | string | — (obligāts) | Tehniskais identifikators: spool mapes nosaukums un keša atslēga. |
| `name` | string | — (obligāts) | Attēlojamais nosaukums; e-pasta tēmā `{name}`. |
| `host` | string | — (obligāts) | Printera IP / hostname. |
| `port` | int | `631` | IPP ports. |
| `username`, `password` | ?string | `null` | Autentifikācija, ja printeris to prasa. |
| `curlOptions` | array | `[]` | Papildu cURL opcijas, ko nodod `obray\ipp\Printer`. |
| `pageOrientation` | int | `3` (portrait) | IPP `orientation-requested`. |
| `pageSize` | string | `iso_a4_210x297mm` | IPP `media`. |
| `spoolerComponentName` | string | — | Obligāts, ja izmanto spool. |
| `alertConfigComponentName` | ?string | `null` | Obligāts statusa iegūšanai. |
| `mailerComponentName` | ?string | `null` | Obligāts e-pasta brīdinājumiem. |
| `cacheComponentName` | ?string | `null` | Ja nav norādīts, panelis katru reizi veido statusu bez printera vaicājuma. |
| `pincode`, `encryption`, `timeout`, `daemonName`, `cacheDuration` | | | Rezervēti; pašlaik kodā netiek izmantoti. |

Pieejamās konstantes (`types\PrinterAttributeValues`):

- orientācija: `ORIENTATION_PORTRAIT` (3), `ORIENTATION_LANDSCAPE` (4), `ORIENTATION_REVERSE_LANDSCAPE` (5), `ORIENTATION_REVERSE_PORTRAIT` (6);
- formāts: `MEDIA_SIZE_A2`, `MEDIA_SIZE_A3`, `MEDIA_SIZE_A4`, `MEDIA_SIZE_A5`;
- kvalitāte: `PRINT_QUALITY_DRAFT`, `PRINT_QUALITY_NORMAL`, `PRINT_QUALITY_HIGH` (pašlaik drukāšanā netiek nodota).

Ja vajag drukāt dažādos formātos (piem., A4 un A5 uzlīmes) uz viena fiziskā printera, izveido divas komponentes ar vienu `host`, bet atšķirīgu `printerName` un `pageSize`.

---

## 4. Lietošana kodā

### 4.1 Drukāšana caur rindu (ieteicamais veids)

```php
/** @var \d3yii2\d3printeripp\components\BasePrinter $printer */
$printer = Yii::$app->get('printerOffice');
$printer->printToSpoolDirectory('/path/to/invoice.pdf', 2); // 2 kopijas
```

Fails tiek nokopēts uz `runtime/d3printer/spool_printerOffice/` (kopijas — `invoice1.pdf`, `invoice2.pdf`). Faktisko drukāšanu veic daemon (6.3). Lietotāja pieprasījums netiek bremzēts un printera kļūda (nav papīra, izslēgts) nepazaudē dokumentu — fails paliek rindā, līdz izdrukājas.

> Ja rindā jau ir fails ar tādu pašu nosaukumu, tas tiks pārrakstīts. Izmantot unikālus failu nosaukumus (piem., ar dokumenta ID un laiku).

### 4.2 Tieša drukāšana

```php
$payload = $printer->printFile('/path/to/file.pdf', 1);   // obray\ipp\transport\IPPPayload
$payload = $printer->printContent($binaryContent, 1);
```

Ja printeris neatgriež `successful-ok`, tiek mests `yii\base\Exception` (piem., `Error printing document. Error: server-error-busy`).

Saturs tiek nosūtīts printerim bez konvertācijas, tāpēc formātam jābūt tādam, ko printeris saprot. HP LaserJet Pro 3002dn atbalsta PDF, PostScript un PCL.

### 4.3 Citas metodes

| Metode | Apraksts |
|---|---|
| `getStatusFromPrinter(bool $withAttributes = true): AlertConfig` | Vaicā printeri, aizpilda noteikumus, saglabā kešā. |
| `getStatusFromCache(): AlertConfig` | No keša; ja keša nav — statuss bez printera vaicājuma. |
| `getPrinterAttributes(): obray\ipp\PrinterAttributes` | Visi IPP atribūti. Ja cURL kļūda — `printer-state = offline`. |
| `getAllAttributes(): array` | Atribūti kā masīvs. |
| `getUncompletedJobs(): IPPPayload` | Nepabeigtie darbi printerī (`Get-Jobs`, `not-completed`). |
| `getSpoolDirectoryFiles(): array`, `countSpoolDirectoryFiles(): int` | Rindas faili. |
| `deleteSpoolDirectoryFile(string $filename): bool` | Dzēš failu no rindas (pilns ceļš). |
| `getSpoolDirectory(): string` | Rindas mape relatīvi pret `runtime`. |
| `sendAlertEmail(AlertConfig $alert): void` | Nosūta e-pastu ar stāvokli. |

---

## 5. Stāvokļa noteikumi (AlertConfig un rules)

Projektā izveido klasi, kas manto `AlertConfig` un definē, kurus noteikumus rādīt un pārbaudīt. Piemērs: `doc/examples/IppPrinter3002dnAlertConfig.php`.

```php
namespace app\components;

use d3yii2\d3printeripp\components\AlertConfig;
use d3yii2\d3printeripp\components\rules\{
    PrinterInfo, FilesCountInSpooler, PrinterState, PrinterStateReasons,
    PrinterInputTray, MarkerLevels, Updated, PrintTestPage, ReloadStatus
};

class IppPrinter3002dnAlertConfig extends AlertConfig
{
    public function rules(): array
    {
        return [
            ['className' => PrinterInfo::class],
            ['className' => FilesCountInSpooler::class],
            ['className' => PrinterState::class],
            ['className' => PrinterStateReasons::class],
            ['className' => PrinterInputTray::class],
            ['className' => MarkerLevels::class, 'minValue' => 10],
            ['className' => Updated::class],
            ['className' => PrintTestPage::class],
            ['className' => ReloadStatus::class],
        ];
    }
}
```

Katrs masīva elements: `className` + jebkuras publiskās noteikuma īpašības (piem., `minValue`).

### 5.1 Pieejamie noteikumi

| Noteikums | IPP atribūts | Etiķete | Brīdinājums / kļūda |
|---|---|---|---|
| `PrinterInfo` | `printer-info` | Informācija | — |
| `PrinterState` | `printer-state` | Statuss | **Kļūda**, ja `stopped` vai `offline`. |
| `PrinterStateReasons` | `printer-state-reasons` | Iemesls | **Kļūda**, ja iemesls nav `none` (nav papīra, nav tonera, atvērtas durvis, spūleris pilns u.c.). |
| `PrinterInputTray` | `printer-input-tray` | Papīra padeve | **Kļūda**, ja `status` ≠ 0 (19 = nav papīra). Viena rinda katrai paplātei. |
| `MarkerLevels` | `marker-levels` | Kārtridžs | **Brīdinājums**, ja līmenis < `minValue` (noklusējums 20 %). |
| `FilesCountInSpooler` | — (rinda) | Faili spūlerī | **Kļūda**, ja rindā ir faili (daemon nedrukā vai printeris nepieejams). |
| `Updated` | — | Informācija atjaunota | Datu nolasīšanas laiks. |
| `PrintTestPage` | — | Izdruka | Panelī — testa izdrukas poga. |
| `ReloadStatus` | — | Atjaunot | Panelī — statusa atjaunošanas poga. |

Noteikumu tipi (`RulesInterface`): `TYPE_RULE` — vērtība no IPP atribūta; `TYPE_OTHER` — aprēķināta vērtība; `TYPE_PRINT`, `TYPE_RELOAD` — paneļa pogas.

### 5.2 Sava noteikuma izveide

Implementē `components\rules\RulesInterface`:

```php
namespace app\components\printer;

use d3yii2\d3printeripp\components\rules\RulesInterface;

class QueuedJobCount implements RulesInterface
{
    private int $value;
    public int $maxValue = 5;

    public function __construct(int $value) { $this->value = $value; }

    public static function getAttributeName(): string { return 'queued-job-count'; }
    public static function getType(): string { return self::TYPE_RULE; }
    public function getLabel(): string { return 'Darbi rindā printerī'; }
    public function getValueLabel() { return $this->value; }
    public function isWarning(): bool { return $this->value > $this->maxValue; }
    public function isError(): bool { return false; }
    public function getWarningMessage(): string { return $this->isWarning() ? 'Printerī rindā ' . $this->value . ' darbi' : ''; }
    public function getErrorMessage(): string { return ''; }
}
```

Lai uzzinātu, kādi atribūti un vērtības printerim pieejami: `php yii printeripp/attributes printerOffice [atribūta-nosaukums]`.

`TYPE_RULE` noteikumam konstruktors saņem atribūta vērtību. Ja atribūtam ir vairākas vērtības (piem., vairākas paplātes), katrai tiek izveidots atsevišķs noteikuma objekts. `TYPE_OTHER/PRINT/RELOAD` noteikumi tiek veidoti bez argumentiem, un `BasePrinter` aizpilda to publiskās īpašības `printerComponentName`, `dataUpdatedTime`, `countFilesInSpooler`, ja tādas ir.

### 5.3 E-pasta brīdinājuma loģika

`AlertConfig::warningMustBeSent($prev)` atgriež `true`, ja brīdinājumu ziņojumu saraksts atšķiras no iepriekšējā (keša) stāvokļa **un** pašlaik ir brīdinājums vai kļūda. Tātad viena un tā pati problēma e-pastu nesūta atkārtoti katrā pārbaudē.

---

## 6. Konsoles komandas

### 6.1 Statuss un monitorings

```bash
# parādīt stāvokli
php yii printeripp/status printerOffice

# pārbaudīt un, ja stāvoklis mainījies, nosūtīt brīdinājumu (monitorings, crontab)
php yii printeripp/status printerOffice 1

# nosūtīt testa brīdinājuma e-pastu (pārbauda Mailer konfigurāciju)
php yii printeripp/status printerOffice 0 1
```

Parametri: `printerComponentName`, `chackAndSendAlertMessage` (0/1), `sendAlertMessage` (0/1).

Crontab piemērs (katras 5 minūtes):

```
*/5 * * * * cd /var/www/cewood && php yii printeripp/status printerOffice 1 >> runtime/logs/printer-monitor.log 2>&1
```

Vairākiem printeriem — katram sava rinda. Ja `printerStatusCache.cacheDuration` ir īsāks par crontab intervālu, iepriekšējais stāvoklis būs izzudis un brīdinājums var tikt nosūtīts atkārtoti — `cacheDuration` iestatīt garāku par pārbaudes intervālu.

### 6.2 Atribūti un testa druka

```bash
# visi printera IPP atribūti
php yii printeripp/attributes printerOffice
# viens atribūts
php yii printeripp/attributes printerOffice marker-levels

# testa lapa (PostScript) + nepabeigto darbu saraksts pirms un pēc
php yii printeripp/test-print printerOffice
```

### 6.3 Drukas rindas daemon

```bash
php yii printeripp-spool/index spoolerPrinting
# vai pārrakstot printeru sarakstu:
php yii printeripp-spool/index spoolerPrinting printerOffice,printerWarehouse
```

Darbība:

- cikls ik pēc 1 s; process pats beidzas pēc 1 h (jāpārstartē ar supervisor/systemd);
- katram printerim nolasa rindas failus un drukā ar `printFile()`;
- pēc veiksmīgas drukas fails tiek dzēsts;
- ja printeris atbild `server-error-busy`, šī printera apstrāde tiek pārtraukta līdz nākamajam ciklam;
- citas kļūdas tiek žurnalētas vienreiz (atkārtojumos izvada `E`), fails paliek rindā un tiek mēģināts atkārtoti;
- žurnāls: `runtime/logging/printer-spooler/`.

Supervisor piemērs:

```ini
[program:printer-spooler]
command=php /var/www/cewood/yii printeripp-spool/index spoolerPrinting
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/www/cewood/runtime/logs/printer-spooler.out.log
redirect_stderr=true
```

Daemon jāpalaiž ar to pašu lietotāju, ar kuru web aplikācija raksta rindas failus, citādi faili netiks izdzēsti.

---

## 7. Dashboard panelis

```php
$config['components']['dashboard']['panels']['notifications'][] = [
    'route' => '/d3printeripp/printer-panel/dashboard',
    'params' => ['printerComponentName' => 'printerOffice'],
    'tag' => 'div',
    'options' => ['class' => 'col-sm-6 col-md-4 col-lg-3'],
];
```

Panelis:

- rāda IP, komponentes nosaukumu un visus `AlertConfig` noteikumus no keša (printeris netiek vaicāts katrā lapas ielādē);
- krāsa: sarkans — kļūda, dzeltens — brīdinājums, zaļš — viss kārtībā (sakļauts);
- virsraksts ir saite uz printera EWS (`https://<host>`);
- pogas: **Izdruka** (`/d3printeripp/printer/print` — testa lapa) un **Atjaunot** (`/d3printeripp/printer/reload-status` — nolasa stāvokli no printera un atjauno kešu).

Pieeja — lomas no `panelViewRoleNames`. Lomu (piem., `D3PrinterViewPanel`) jāizveido RBAC un jāpiešķir lietotājiem. Panelis izmanto `eaBlankonThema` widgetus.

---

## 8. Ieviešanas soļi (kontrolsaraksts)

1. Printerim fiksēta IP, ieslēgts IPP.
2. `composer require d3yii2/d3printeripp` (ar repozitoriju `DBRisinajumi/IPP`).
3. Projektā izveidot `AlertConfig` klasi (5. nodaļa).
4. Konfigurēt komponentes web un console konfigurācijā (3.3).
5. Konsoles `controllerMap` (3.1), web modulis (3.2).
6. Pārbaudīt: `php yii printeripp/attributes <printeris>`, `php yii printeripp/test-print <printeris>`.
7. Pārbaudīt e-pastu: `php yii printeripp/status <printeris> 0 1`.
8. Crontab monitoringam: `php yii printeripp/status <printeris> 1`.
9. Supervisor daemonam: `php yii printeripp-spool/index spoolerPrinting`.
10. Pievienot paneli dashboardā, izveidot RBAC lomu.

---

## 9. Problēmu risināšana

| Simptoms | Iespējamais cēlonis / risinājums |
|---|---|
| Statuss `Offline` | cURL kļūda — printeris izslēgts, nepareiza IP/ports, firewall bloķē 631. Pārbaudīt `curl -v http://<host>:631/ipp/print`. |
| `AuthenticationError` | Printeris prasa autentifikāciju — norādīt `username`/`password`. |
| `Error printing document. Error: client-error-document-format-not-supported` | Printeris nesaprot faila formātu — sūtīt PDF/PS/PCL. |
| `server-error-busy` | Printeris aizņemts; daemon mēģinās atkārtoti. |
| Faili krājas rindā | Daemon nedarbojas vai printeris nepieejams; skatīt `runtime/logging/printer-spooler/`. |
| Fails netiek dzēsts no rindas | Daemon lietotājam nav tiesību dzēst failu (atšķirīgs lietotājs web un CLI). |
| Panelī `Not found printer component` | Komponente nav definēta web konfigurācijā. |
| Brīdinājuma e-pasti atkārtojas | `cacheDuration` īsāks par monitoringa intervālu. |
| Neparādās papīra/tonera dati | Printeris neatgriež atribūtu — pārbaudīt ar `printeripp/attributes`. |

---

## 10. Zināmās nepilnības kodā

Konstatētas, veidojot dokumentāciju; ieteicams izlabot pakotnē.

1. `AlertConfig::getErrorMessages()` izmanto `isWarning()`/`getWarningMessage()` nevis `isError()`/`getErrorMessage()` — kļūdu saraksts konsolē un e-pastā ir nepareizs.
2. `AlertConfig::createEmailBody()` sadaļai "Warnings" pārbauda `hasError()` un ņem `getErrorMessages()` — jābūt `hasWarning()`/`getWarningMessages()`.
3. `warningMustBeSent()` salīdzina tikai brīdinājumu ziņojumus; kļūdu izmaiņas (kopā ar 1. punktu) var netikt pamanītas. Ieteicams salīdzināt arī `getErrorMessages()`.
4. `PrinterStateReasons::REASONS_MAP` atslēga `jamed` — IPP standartā `media-jam`.
5. `PrinterInputTray::getWarningMessage()` un `PrinterStateReasons::getWarningMessage()` atgriež `bool` (`"1"`/`""`) nevis tekstu.
6. `Spooler::sendToSpooler()` pārraksta failu ar tādu pašu nosaukumu; `deleteSpoolFile()` ignorē `$printerName`.
7. README konfigurācijā norādītas nepareizas klases: `d3yii2\d3printer\components\Spooler` (pareizi `d3yii2\d3printeripp\components\Spooler`) un `...\components\cache\PrinterCache` (pareizi `...\components\PrinterCache`); nav aprakstīta `spoolerPrinting` komponente.
8. `composer.json` `autoload` sadaļa (`app\components\` → `src/`) neatbilst moduļa namespace `d3yii2\d3printeripp\`.
9. Mape `logic/` ir neizmantots, nekompilējams kods.
10. `StatusController` ir nepabeigts (atgriež tukšus datus).
11. `BasePrinter` īpašības `pincode`, `encryption`, `timeout` netiek izmantotas; `PRINT_QUALITY_*` netiek nodots drukas darbam.
