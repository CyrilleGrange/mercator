<?php

namespace App\Http\Controllers\Admin;

Use \Carbon\Carbon;

// ecosystem
use App\Entity;
use App\Relation;

// information system
use App\MacroProcessus;
use App\Process;
use App\Activity;
use App\Operation;
use App\Task;
use App\Actor;
use App\Information;

// Applications
use App\ApplicationBlock;
use App\MApplication;
use App\ApplicationService;
use App\ApplicationModule;
use App\Database;
use App\Flux;

// Administration
use App\ZoneAdmin;
use App\Annuaire;
use App\ForestAd;
use App\DomaineAd;

// Logique
use App\Network;
use App\Subnetword;
use App\Gateway;
use App\ExternalConnectedEntity;
use App\NetworkSwitch;
use App\Router;
use App\SecurityDevice;
use App\DhcpServer;
use App\Dnsserver;
use App\LogicalServer;

// Physique
use App\Site;
use App\Building;
use App\Bay;
use App\PhysicalServer;
use App\Workstation;
use App\StorageDevice;
use App\Peripheral;
use App\Phone;
use App\PhysicalSwitch;
use App\PhysicalRouter;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// PhpOffice
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Shared\Converter;
use \PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Chart;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Line;

class CartographyController extends Controller
{

    public function cartography(Request $request) {

        // converter 
        $html = new \PhpOffice\PhpSpreadsheet\Helper\Html();

        // get parameters
        $granularity = $request->granularity;
        $vues = $request->input('vues', []);

        // get template
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        // Numbering Style
        $phpWord->addNumberingStyle(
            'hNum',
            array('type' => 'multilevel', 'levels' => array(
                array('pStyle' => 'Heading1', 'format' => 'decimal', 'text' => '%1.'),
                array('pStyle' => 'Heading2', 'format' => 'decimal', 'text' => '%1.%2.'),
                array('pStyle' => 'Heading3', 'format' => 'decimal', 'text' => '%1.%2.%3.'),
                )
            )
        );
        $phpWord->addTitleStyle(0, 
                array('size' => 28, 'bold' => true), 
                array('align'=>'center'));
        $phpWord->addTitleStyle(1, 
                array('size' => 16, 'bold' => true), 
                array('numStyle' => 'hNum', 'numLevel' => 0));
        $phpWord->addTitleStyle(2, 
                array('size' => 14, 'bold' => true), 
                array('numStyle' => 'hNum', 'numLevel' => 1));
        $phpWord->addTitleStyle(3, 
                array('size' => 12, 'bold' => true), 
                array('numStyle' => 'hNum', 'numLevel' => 2));

        // cell style
        $fancyTableTitleStyle=array("bold"=>true, 'color' => '006699');
        $fancyTableCellStyle=array("bold"=>true, 'color' => '000000');

        // Title
        $section->addTitle("Cartographie du Système d'Information",0);        
        $section->addTextBreak(2);

        // TOC
        $toc = $section->addTOC(array('spaceAfter' => 60, 'size' => 10));
        $toc->setMinDepth(1);
        $toc->setMaxDepth(3);
        $section->addTextBreak(1);

        // page break
        $section->addPageBreak();

        // Add footer
        $footer = $section->addFooter();
        $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', array('size' => 8) , array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
        // $footer->addLink('https://github.com/PHPOffice/PHPWord', 'PHPWord on GitHub');        

        // ====================
        // ==== Ecosystème ====
        // ====================        
        if ($vues==null || in_array("1",$vues)) {
            // schema
            $section->addTitle("Ecosystème", 1);
            $section->addText("La vue de l’écosystème décrit l’ensemble des entités ou systèmes qui gravitent autour du système d’information considéré dans le cadre de la cartographie. Cette vue permet à la fois de délimiter le périmètre de la cartographie, mais aussi de disposer d’une vision d’ensemble de l’écosystème sans se limiter à l’étude individuelle de chaque entité.");
            $section->addTextBreak(1);

            // get all entities
            // $section = $phpWord->addSection();
            $entities = Entity::All()->sortBy("name");

            // get all relations
            $relations = Relation::All()->sortBy("name");

            // Generate Graph
            // $graph="digraph D { A -> {B, C, D} -> {F} }";
            // $graph="digraph D { A -> {B, C, D} -> {F} H -> I J-> A->J C->A K->L  B->Q B->R B->S}";

            $graph = "digraph  {";
            foreach($entities as $entity)
                $graph .= "E". $entity->id . "[label=\"". $entity->name ."\" shape=none labelloc=b width=1 height=1.8 image=\"".public_path("/images/entity.png")."\"]";
            foreach($relations as $relation) 
                $graph .= "E".$relation->source_id ." -> E". $relation->destination_id ."[label=\"". $relation ->name ."\"]";
            $graph .= "}";

            // IMAGE
            // $testImage=public_path('images/cloud.png');
            $image=$this->generateGraphImage($graph);            
            list($width, $height, $type, $attr) = getimagesize($image); 

            /*

            $imageStyle = array(
                'marginTop' => -1,
                'marginLeft' => -1,
                'width' => min($width,6000),
                'height' => min($height,8000),
                'wrappingStyle' => 'square'
            );

            $textRun=$section->addTextRun();
            $textRun->addImage($image, $imageStyle);            
            */

            Html::addHtml($section, '<table style="width:100%"><tr><td><img src="'.$image.'" width="600"/></td></tr></table>');
            $section->addTextBreak(1);


            // ===============================
            $section->addTitle('Entités', 2);
            $section->addText("Partie de l’organisme (ex. : filiale, département, etc.) ou système d’information en relation avec le SI qui vise à être cartographié.");
            $section->addTextBreak(1);

            // loop on entities
            foreach ($entities as $entity) {
                $section->addBookmark("ENTITY".$entity->id);
                $table = $section->addTable(
                        array('borderSize' => 6, 'borderColor' => '006699', 'cellMargin' => 80, 'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::START, 'cellSpacing' => 50));
                $table->addRow();
                $table->addCell(8000,array('gridSpan' => 2,'bold'=>true))
                    ->addText($entity->name,$fancyTableTitleStyle);
                $table->addRow();
                $table->addCell(2000)->addText("Description", $fancyTableCellStyle);
                $table->addCell(6000)->addText(
                    htmlspecialchars($html->toRichTextObject($entity->description))
                    );
                $table->addRow();
                $table->addCell(2000)->addText("Niveau de sécurité",$fancyTableCellStyle);
                $table->addCell(6000)->addText(htmlspecialchars($html->toRichTextObject($entity->security_level)));
                $table->addRow();
                $table->addCell(2000)->addText("Point de contact",$fancyTableCellStyle);
                $table->addCell(6000)->addText($entity->contact_point);
                $table->addRow();
                $table->addCell(2000)->addText("Relations",$fancyTableCellStyle);
                $cell=$table->addCell(6000);
                $textRun=$cell->addTextRun();
                foreach ($entity->sourceRelations as $relation) {
                    if ($relation->id!=null)
                        $textRun->addLink('RELATION'.$relation->id, $relation->name, null, null, true);
                    $textRun->addText(' -> ');
                    if ($relation->destination_id!=null)
                        $textRun->addLink('ENTITY'.$relation->destination_id, $entities->find($relation->destination_id)->name, null, null, true);
                    if ($entity->sourceRelations->last() != $relation) 
                        $textRun->addText(", ");                    
                }
                if ((count($entity->sourceRelations)>0)&&(count($entity->destinationRelations)>0))
                    $textRun->addText(", ");
                foreach ($entity->destinationRelations as $relation) {                    
                    $textRun->addLink('RELATION'.$relation->id, $relation->name, null, null, true);
                    $textRun->addText(htmlspecialchars(' <- '));
                    $textRun->addLink('ENTITY'.$relation->source_id, $entities->find($relation->source_id)->name, null, null, true);
                    if ($entity->destinationRelations->last() != $relation)  
                        $textRun->addText(", ");                    
                }
                $table->addRow();
                $table->addCell(2000)->addText("Processus soutenus",$fancyTableCellStyle);
                $table->addCell(6000)->addText("");
                $section->addTextBreak(1);
            }

            // ===============================
            $section->addTextBreak(2);
            $section->addTitle('Relations', 2);
            $section->addText("Lien entre deux entités ou systèmes.");
            $section->addTextBreak(1);

            // loop on relations
            foreach ($relations as $relation) {
                Log::debug('RELATION'.$relation->id);
                $section->addBookmark("RELATION".$relation->id);
                $table = $section->addTable(
                        array('borderSize' => 6, 'borderColor' => '006699', 'cellMargin' => 80, 'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::START, 'cellSpacing' => 50));
                $table->addRow();
                $table->addCell(8000,array('gridSpan' => 2))->addText($relation->name,$fancyTableTitleStyle);
                $table->addRow();
                $table->addCell(2000)->addText("Description",$fancyTableCellStyle);
                $table->addCell(6000)->addText(htmlspecialchars($html->toRichTextObject($relation->description)));
                $table->addRow();
                $table->addCell(2000)->addText("Type",$fancyTableCellStyle);
                $table->addCell(6000)->addText($relation->type);
                $table->addRow();
                $table->addCell(1500)->addText("Importance",$fancyTableCellStyle);
                if ($relation->inportance==1) 
                    $table->addCell(6000)->addText('Faible');
                elseif ($relation->inportance==2)
                    $table->addCell(6000)->addText('Moyen');
                elseif ($relation->inportance==3)
                    $table->addCell(6000)->addText('Fort');
                elseif ($relation->inportance==4)
                    $table->addCell(6000)->addText('Critique');
                else
                    $table->addCell(6000)->addText("");
                $table->addRow();
                $table->addCell(2000)->addText("Lien",$fancyTableCellStyle);
                $cell=$table->addCell(6000);
                $textRun=$cell->addTextRun();
                $textRun->addLink('ENTITY'.$relation->source_id, $entities->find($relation->source_id)->name, null, null, true);
                $textRun->addText(" -> ");
                $textRun->addLink('ENTITY'.$relation->destination_id, $entities->find($relation->destination_id)->name, null, null, true);
                $section->addTextBreak(1);
            }

        }

        // <option value="2">Système d'information</option>
        if ($vues==null || in_array("2",$vues)) {
            $section->addTextBreak(2);
            $section->addTitle("Système d'information", 1);
            $section->addText("La vue métier du système d’information décrit l’ensemble des processus métiers de l’organisme avec les acteurs qui y participent, indépendamment des choix technologiques faits par l’organisme et des ressources mises à sa disposition. La vue métier est essentielle, car elle permet de repositionner les éléments techniques dans leur environnement métier et ainsi de comprendre leur contexte d’emploi.");
            $section->addTextBreak(1);

            // =====================================
            $section->addTitle('Macro-processus', 2);
            $section->addText("Ensemble de processus.");
            $section->addTextBreak(1);

            // =====================================
            $section->addTitle('Macro-processus', 2);
            $section->addText("Ensemble d’activités concourant à un objectif. Le processus produit des informations (de sortie) à valeur ajoutée (sous forme de livrables) à partir d’informations (d’entrées) produites par d’autres processus.");
            $section->addTextBreak(1);

            // =====================================
            $section->addTitle('Opérations', 2);
            $section->addText("Étape d’une procédure correspondant à l’intervention d’un acteur dans le cadre d’une activité.");
            $section->addTextBreak(1);
        }

        // <option value="3">Applications</option>
        if ($vues==null || in_array("3",$vues)) {
            $section->addTextBreak(2);
            $section->addTitle("Applications", 1);
            $section->addText("La vue des applications permet de décrire une partie de ce qui est classiquement appelé le « système informatique ». Cette vue décrit les solutions technologiques qui supportent les processus métiers, principalement les applications.");
            $section->addTextBreak(1);


        }

        // <option value="4">Administration</option>
        if ($vues==null || in_array("4",$vues)) {
            $section->addTextBreak(2);
            $section->addTitle("Administration", 1);
        }

        // <option value="5">Infrastructure physique</option>
        if ($vues==null || in_array("5",$vues)) {
            $section->addTextBreak(2);
            $section->addTitle("Infrastructure physique", 1);
        }

        // <option value="6">Infrastructure logique</option>
        if ($vues==null || in_array("6",$vues)) {
            $section->addTextBreak(2);
            $section->addTitle("Infrastructure logique", 1);
        }

        // Finename
        $filepath=storage_path('app/reports/cartographie-'. Carbon::today()->format("Ymd") .'.docx');

        // Saving the document as Word2007 file.
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filepath);

        // unlink files
        unlink($image); // ??

        // return
        return response()->download($filepath);       
    }

    // Generate the image of the graph from a dot notation using GraphViz
    private function generateGraphImage(String $graph) {

        // Save it to a file
        $dot_path = tempnam("/tmp","dot");
        $dot_file = fopen($dot_path, 'w');
        fwrite($dot_file, $graph);
        fclose($dot_file);

        // create image file
        $png_path = tempnam("/tmp","png");

        // dot -Tpng ./test.dot -otest.png
        shell_exec ("/usr/bin/dot -Tpng ".$dot_path." -o".$png_path);

        // delete graph file
        unlink($dot_path);

        // return file path (do not forget to delete after...)
        return $png_path;
    }

}

