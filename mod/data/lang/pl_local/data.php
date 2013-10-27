<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local language pack from http://proba.edu.p.lodz.pl
 *
 * @package    mod
 * @subpackage data
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actionname'] = 'Nazwa akcji:';
$string['actionnameexists'] = 'Akcja o takiej nazwie dla wybranego stanu startowego już istnieje ({$a})';
$string['actions'] = 'Akcje:';
$string['addaction'] = 'Dodaj akcję';
$string['addtemplate'] = 'Szablon dodawania';
$string['allowstateforrole'] = 'Zezwól użytkownikom w roli {$a->fromrole} na wykonywanie akcji przepływu pracy w stanie {$a->state}';
$string['asearchtemplate'] = 'Szablon zaawansowanego wyszukiwania';
$string['availableactions'] = '[Akcje] : Stan startowy &gt;&gt; Stan docelowy';
$string['availabletags'] = 'Dostępne znaczniki';
$string['cannotaccesspresentsother'] = 'Nie masz dostępu do ustawień wstępnych innych użytkowników';
$string['cannotdeletepreset'] = 'Błąd przy usuwaniu ustawień wstępnych!';
$string['cannotoverwritepreset'] = 'Błąd przy nadpisywaniu ustawień wstępnych!';
$string['cannotunziptopreset'] = 'Nie można rozpakować pliku zip do katalogu ustawień wstępnych';
$string['configallowstateforrole'] = 'Zezwalaj użytkownikom w rolach na górze na wykonywanie akcji przepływu pracy w określonych stanach dla wpisów w bazie danych';
$string['createstate'] = 'Utwórz stan';
$string['createworkflow'] = 'Utwórz przepływ pracy';
$string['csstemplate'] = 'Szablon CSS';
$string['currentcourse'] = 'Aktualny kurs';
$string['data:managetemplates'] = 'Zarządzaj szablonami';
$string['data:manageuserpresets'] = 'Zarządzaj wszystkimi ustawieniami wstępnymi szablonów';
$string['data:manageworkflows'] = 'Zarządzaj przepływami pracy';
$string['data:superviseworkflow'] = 'Otrzymuj powiadomienia o zmianach stanu przepływu pracy dla wpisów bazy danych';
$string['data:useworkflow'] = 'Zmieniaj stan przepływu pracy dla wpisów bazy danych';
$string['data:viewallentries'] = 'Przeglądaj wpisy wszystkich użytkowników';
$string['data:viewalluserpresets'] = 'Przeglądaj ustawienia wstępne wszystkich użytkowników';
$string['date'] = 'Data';
$string['dateentered'] = 'Data utworzenia';
$string['defaultsortfield'] = 'Domyślne pole sortowania';
$string['deleteaction'] = 'Usuń akcję';
$string['deleteactionconfirm'] = 'Czy na pewno usunąć tę akcję? {$a}';
$string['deleteselectedaction'] = 'Usuń wybraną akcję';
$string['deleteselectedstate'] = 'Usuń wybrany stan';
$string['deleteselectedworkflow'] = 'Usuń wybrany przepływ pracy';
$string['deletestate'] = 'Usuń stan';
$string['deletestateconfirm'] = 'Czy na pewno usunąć ten stan? {$a}';
$string['deletewarning'] = 'Masz całkowitą pewność, że chcesz usunąć to ustawienie wstępne?';
$string['deleteworkflow'] = 'Usuń przepływ pracy';
$string['deleteworkflowconfirm'] = 'Masz całkowitą pewność, że chcesz usunąć ten przepływ pracy? {$a}';
$string['editaction'] = 'Edytuj akcję';
$string['editstate'] = 'Edytuj stan';
$string['editworkflow'] = 'Edytuj przepływ pracy';
$string['erroreditaction'] = 'Błąd podczas edycji akcji przepływu pracy';
$string['erroreditstate'] = 'Błąd podczas edycji stanu przepływu pracy';
$string['erroreditworkflow'] = 'Błąd podczas edycji przepływu pracy';
$string['errorpresetexists'] = 'Istnieje już ustawienie wstępne o wskazanej nazwie';
$string['exportaszip'] = 'Eksportuj jako plik zip';
$string['exportedtozip'] = 'Eksport jako tymczasowy plik zip...';
$string['failedpresetdelete'] = 'Błąd przy usuwaniu ustawień wstępnych!';
$string['file'] = 'Plik';
$string['headercsstemplate'] = 'Zdefiniuj lokalny styl CSS dla innych szablonów';
$string['headerjstemplate'] = 'Zdefiniuj własny Javascript dla innych szablonów';
$string['importsuccess'] = 'Ustawienia wstępne zostały skutecznie zastosowane.';
$string['includeapproval'] = 'Uwzględnij status zatwierdzenia wpisu';
$string['includetime'] = 'Uwzględnij czas dodania/zmiany';
$string['includeuserdetails'] = 'Uwzględnij dane użytkownika';
$string['includewfstate'] = 'Uwzględnij stan przepływu pracy';
$string['incorrectstate'] = 'Niewłaściwy stan!';
$string['initstate'] = 'Stan początkowy';
$string['invalidpreset'] = '{$a} nie zawiera ustawień wstępnych.';
$string['jstemplate'] = 'Szablon Javascript';
$string['listtemplate'] = 'Szablon listy';
$string['localworkflow'] = 'Przepływ pracy dla kursu';
$string['localworkflow_help'] = 'Określa czy przepływ pracy jest widoczny tylko dla bieżącego kursu, lub czy może być używany we wszystkich kursach. Tylko administrator może zmienić to ustawienie.';
$string['namefile'] = 'Pole plik';
$string['newentry'] = 'Nowy wpis';
$string['newfield'] = 'Utwórz nowe pole';
$string['nodefinedfields'] = 'Nowe ustawienia wstępne nie mają zdefiniowanych pól!';
$string['nodelete'] = 'Usunięcie tego wpisu jest niedozwolone';
$string['noedit'] = 'Edycja tego wpisu jest niedozwolona';
$string['nolisttemplate'] = 'Szablon listy nie został jeszcze zdefiniowany';
$string['nosingletemplate'] = 'Pojedynczy szablon nie został jeszcze zdefiniowany';
$string['nostatechange'] = 'Zmiana stanu tego wpisu jest niedozwolona';
$string['nostates'] = '-- brak zdefiniowanych stanów --';
$string['notifyboth'] = 'Powiadamianie TWÓRCY oraz NADZORCY';
$string['notifycreator'] = 'Powiadamianie TWÓRCY';
$string['notifynobody'] = 'NIE POWIADAMIAJ nikogo';
$string['notifysupervisor'] = 'Powiadamiaj NADZORCĘ';
$string['number'] = 'Numer';
$string['presets'] = 'Ustawienia wstępne';
$string['rsstemplate'] = 'Szablon RSS';
$string['rsstitletemplate'] = 'Tytuł szablonu RSS';
$string['saveaspreset'] = 'Zapisz jako ustawienia wstępne';
$string['savesuccess'] = 'Zapisano poprawnie. Twoje ustawienia wstępne będą teraz dostępne na całej witrynie.';
$string['savetemplate'] = 'Zapisz szablon';
$string['selectedworkflow'] = 'Wybrany przepływ pracy:';
$string['selectstate'] = '-- wybierz stan --';
$string['selectworkflow'] = '-- wybierz przepływ pracy --';
$string['showtargetsforstate'] = 'Pokaż stany docelowe';
$string['showwfstates'] = 'Pokaż stany';
$string['singletemplate'] = 'Pojedynczy szablon';
$string['startstate'] = 'Stan startowy';
$string['startstates'] = 'Stany startowe';
$string['state'] = 'Stan';
$string['statename'] = 'Nazwa stanu';
$string['statenameexists'] = 'Stan o takiej nazwie już istnieje ({$a})';
$string['statenotification'] = 'Powiadomienia przez e-mail';
$string['statenotificationemailbody'] = 'Użytkownik "{$a->user}" zmienił stan wpisu w "{$a->db}" na "{$a->state}"

{$a->url}';
$string['statenotificationemailsubject'] = '[{$a->course}] Zmiana w bazie danych {$a->db}';
$string['statenotification_help'] = 'Kto będzie powiadamiany przez wysłanie e-mail, kiedy wpis przechodzi do tego stanu. Domyślnie jest to TWÓRCA wpisu.';
$string['states'] = 'Stany';
$string['subplugintype_datapreset'] = 'Ustawienie wstępne';
$string['subplugintype_datapreset_plural'] = 'Ustawienia wstępne';
$string['targetstate'] = 'Stan docelowy';
$string['templates'] = 'Szablony';
$string['templatesaved'] = 'Szablon zachowany';
$string['timeadded'] = 'Czas dodania';
$string['timemodified'] = 'Czas modyfikacji';
$string['usestandard'] = 'Wykorzystaj ustawienia wstępne';
$string['wfactions'] = 'Akcje';
$string['wfactionunavailable'] = '(niedostępne w aktualnym stanie)';
$string['wfallows'] = 'Dozwolone role dla stanów';
$string['wfdefinitions'] = 'Definicje';
$string['wfdefslist'] = 'Lista zdefiniowanych przepływów pracy';
$string['workflow'] = 'Przepływ pracy';
$string['workflowenable'] = 'Włącz przepływ pracy';
$string['workflowname'] = 'Nazwa przepływu pracy';
$string['workflownameexists'] = 'Przepływ pracy o takiej nazwie już istnieje ({$a})';
$string['workflows'] = 'Przepływy pracy';
$string['workflowstate'] = 'Stan przepływu pracy';
