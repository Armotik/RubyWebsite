<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Exception;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use ImagickPixelIteratorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Image;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ApiImagesController extends AbstractController
{

    private array $rectangleCoordinates = [880, 445, 1825, 630];
    private array $textCoordinates = [450, 650];

    #[Route('/api/images/{username}', name: 'app_api_images_rankup_modtest', methods: ['POST'])]
    public function index(string $username, UserRepository $userRepository): JsonResponse
    {

        $staff = $userRepository->findOneBy(['username' => $username]);

        if (!$staff) {
            return new JsonResponse("Staff not found", Response::HTTP_NOT_FOUND);
        }

        $rank = $staff->getRoles();

        // sort the array in descending order from the highest to the lowest rank (ROLE_WEBMASTER, ROLE_ADMIN, ROLE_SUPERMOD, ROLE_USER)

        usort($rank, function ($a, $b) {
            $hierarchy = [
                'DERANK_STAFF' => 11,
                'DERANK_JOURNALIST' => 10,
                'ROLE_ADMIN_MANAGER' => 9,
                'ROLE_ADMIN' => 8,
                'ROLE_SUPER_MOD' => 7,
                'ROLE_MOD_PLUS' => 6,
                'ROLE_MOD' => 5,
                'ROLE_MOD_TEST' => 4,
                'ROLE_GUIDE' => 3,
                'ROLE_USER' => 2,
                'ROLE_WEBMASTER' => 1,
                'ROLE_BOT' => 0
            ];
            return $hierarchy[$b] - $hierarchy[$a];
        });

        $highestRank = $rank[0];

        $imageURL = 'img/templates/' . $this->selectImage($highestRank)['image'];

        if (!$imageURL) {
            return new JsonResponse("Image not found", Response::HTTP_NOT_FOUND);
        }

        $skinImage = $this->getNationsGlorySkin($username);
        $color = $this->selectImage($highestRank)['color'];
        $imagePath = 'img/tmp/skins/' . $username . '.png';
        $outputPath = 'img/tmp/rankup/';

        $points = [
            "0; 0",
            "0; 402",
            "110; 461",
            "116; 461.5",
            "120; 462",
            "123; 462",
            "124; 462.25",
            "126; 462.5",
            "126.5; 462.75",
            "126.75; 462.75",
            "127; 463",
            "127.5; 462.75",
            "130; 462.5",
            "131; 462",
            "134; 461.5",
            "137; 460",
            "141; 458",
            "245; 402",
            "245; 0",

        ];

        try {
            $this->downloadImage($skinImage, $imagePath, $points);


            $this->replaceSkinOnTemplate($username, $imagePath, $imageURL, $outputPath);

            $newTemplate = $outputPath . $username . '_rankup.png';

            $finaleTemplate = $this->placeRectangle($newTemplate, $color);

            $this->drawTextWithinRectangle($finaleTemplate, $username, $outputPath);

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse("An error occurred: " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws Exception
     */
    private function downloadImage($imageUrl, $savePath, $points): void
    {
        $imageContent = file_get_contents($imageUrl);
        if ($imageContent === false) {
            throw new \Exception("Failed to download image.");
        }

        $skin = new Imagick();
        $skin->readImageBlob($imageContent);

        $this->cutSkinToShape($skin, $points, $savePath);
    }

    private function selectImage(string $url): array
    {

        $images = [
            'ROLE_ADMIN' => ['image' => 'RankUpAdmin.png', 'color' => "#e82446"],
            'ROLE_SUPER_MOD' => ['image' => 'RankUpSM.png', 'color' => "#3b49c2"],
            'ROLE_MOD_PLUS' => ['image' => 'RankUpModPlus.png', 'color' => "#3e8d23"],
            'ROLE_MOD' => ['image' => 'RankUpMod.png', 'color' => '#7ac95f'],
            'ROLE_MOD_TEST' => ['image' => 'RankUpModTest.png', 'color' => '#a2e58b'],
            'ROLE_GUIDE' => ['image' => 'RankUpGuide.png', 'color' => "#ae6eee"],
            'ROLE_JOURNALIST' => ['image' => 'RankUpRoleplay.png', 'color' => "#c80425"],
            'DERANK_JOURNALIST' => ['image' => 'DerankRoleplay.png', 'color' => "#c70727"],
            'DERANK_STAFF' => ['image' => 'DerankStaff.png', 'color' => "#c80425"],
        ];


        return $images[$url];
    }

    private function getNationsGlorySkin(string $username): string
    {

        return "https://skins.nationsglory.fr/body/$username/3d/16";
    }

    /**
     * @throws ImagickException
     */
    private function replaceSkinOnTemplate($username, $skinImagePath, $templatePath, $outputPath): void
    {
        $template = new Imagick($templatePath);

        // Charger le nouveau skin
        $skin = new Imagick($skinImagePath);

        // Ajuster les dimensions du skin pour qu'il corresponde à la zone cible sur le template
        // Ces valeurs doivent être ajustées en fonction des dimensions souhaitées
        $skinWidth = 480; // Largeur du skin sur le template
        $skinHeight = 960; // Hauteur du skin sur le template
        $skin->resizeImage($skinWidth, $skinHeight, Imagick::FILTER_LANCZOS, 1);

        // Les coordonnées où placer le skin sur le template
        // Ces valeurs doivent être ajustées pour positionner le skin correctement sur le template
        $x = 210; // Coordonnée X pour le placement du skin sur le template
        $y = 150;  // Coordonnée Y pour le placement du skin sur le template

        // Superposer le skin sur le template
        $template->compositeImage($skin, Imagick::COMPOSITE_OVER, $x, $y);

        // Sauvegarder l'image résultante
        $outputFilename = $username . '_rankup.png';
        $fullOutputPath = $outputPath . $outputFilename;
        $template->writeImage($fullOutputPath);

        // Nettoyer
        $template->clear();
        $template->destroy();
        $skin->clear();
        $skin->destroy();
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     */
    private function placeRectangle($imagePath, string $color): Imagick
    {
        $imagick = new Imagick($imagePath);

        // Définir les propriétés pour le rectangle couvrant l'ancien nom
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel($color)); // La couleur doit correspondre à celle du fond du rectangle

        // Dessiner le rectangle
        $draw->rectangle($this->rectangleCoordinates[0], $this->rectangleCoordinates[1], $this->rectangleCoordinates[2], $this->rectangleCoordinates[3]);
        $imagick->drawImage($draw);

        return $imagick;
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    private function drawTextWithinRectangle(Imagick $image, string $username, string $outputPath): void
    {
        // Définir les coordonnées du rectangle
        [$x1, $y1, $x2, $y2] = $this->rectangleCoordinates;
        $rectangleWidth = $x2 - $x1;
        $rectangleHeight = $y2 - $y1;

        // Créer un objet de dessin pour le texte
        $draw = new ImagickDraw();
        $draw->setFillColor('white'); // La couleur du texte
        $draw->setFont('font/OpenSans/static/OpenSans-ExtraBold.ttf'); // La police du texte


        // Définir la taille initiale de la police et l'ajuster
        $fontSize = 10; // Commencez petit pour ajuster
        $draw->setFontSize($fontSize);

        // Obtenir les propriétés de la police à la taille actuelle
        $metrics = $image->queryFontMetrics($draw, $username);

        // Augmenter la taille de la police jusqu'à ce que le texte atteigne la largeur ou la hauteur maximale du rectangle
        while ($metrics['textWidth'] <= $rectangleWidth && $metrics['textHeight'] <= $rectangleHeight) {
            $fontSize++;
            $draw->setFontSize($fontSize);
            $metrics = $image->queryFontMetrics($draw, $username);
        }

        // Réduire la taille de la police pour la dernière fois pour s'assurer qu'elle rentre dans le rectangle
        $fontSize--;
        $draw->setFontSize($fontSize);

        // Calculer les coordonnées x et y pour centrer le texte dans le rectangle
        $textX = $x1 + ($rectangleWidth - $metrics['textWidth']) / 2;
        $textY = $y1 + ($rectangleHeight - $metrics['textHeight']) / 2 + $metrics['ascender'];

        // Dessiner le texte sur l'image
        $draw->annotation($textX, $textY, $username);
        $image->drawImage($draw);

        // Libérer les ressources
        $draw->clear();
        $draw->destroy();

        // Sauvegarder l'image
        $outputFilename = $username . '_rankup.png';
        $fullOutputPath = $outputPath . $outputFilename;
        $image->writeImage($fullOutputPath);

        // Libérer les ressources
        $image->clear();
        $image->destroy();
    }

    /**
     * @throws ImagickDrawException
     * @throws ImagickException
     */
    private function cutSkinToShape(Imagick $skin, $pointsStringArray, $outputPath): void
    {
        // Créer le masque basé sur la forme géométrique donnée par les points
        $mask = new Imagick();
        $mask->newImage($skin->getImageWidth(), $skin->getImageHeight(), 'transparent');
        $mask->setImageFormat('png');

        $draw = new ImagickDraw();
        $draw->setFillColor('black');

        // Convertit la chaîne de points en tableau de points pour Imagick
        $polygonPoints = $this->convertPointsToPolygon($pointsStringArray);

        // Dessine la forme sur le masque
        $draw->polygon($polygonPoints);
        $mask->drawImage($draw);

        // Applique le masque sur le skin pour couper la forme
        $skin->compositeImage($mask, Imagick::COMPOSITE_DSTIN, 0, 0);

        // Sauvegarde le skin modifié
        $skin->writeImage($outputPath);

        // Libération des ressources
        $draw->clear();
        $draw->destroy();
        $mask->clear();
        $mask->destroy();
    }

    private function convertPointsToPolygon($pointsStringArray): array
    {
        $pointsArray = [];
        foreach ($pointsStringArray as $pointString) {
            [$x, $y] = explode(';', $pointString);
            $pointsArray[] = ['x' => trim($x), 'y' => trim($y)];
        }
        return $pointsArray;
    }
}
