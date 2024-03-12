<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Exception;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class ApiImagesController extends AbstractController
{

    private array $rectangleCoordinates = [880, 445, 1825, 630];

    /**
     * Generate the image for the staff based on the role and the skin from the NationsGlory API
     * @param string $username The username of the staff
     * @param UserRepository $userRepository The user repository
     * @param Request $request The request
     * @param SerializerInterface $serializer The serializer
     * @return JsonResponse The response
     */
    #[Route('/api/images/{username}', name: 'app_api_images', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_MOD', message: 'Access denied', statusCode: 403)]
    public function index(string $username, UserRepository $userRepository, Request $request, SerializerInterface $serializer): JsonResponse
    {

        $staff = $userRepository->findOneBy(['username' => $username]);

        // Check if the staff exists
        if (!$staff) {
            return new JsonResponse("Staff not found", Response::HTTP_NOT_FOUND);
        }

        $content = $request->getContent();
        $role = $serializer->decode($content, 'json')['role'] ?? null;

        // Check the request content and the role value
        if (empty($content) || $role === null) {
            return new JsonResponse("Role not found", Response::HTTP_BAD_REQUEST);
        }

        $role = $role[0];

        $imageURL = 'img/templates/' . $this->selectImage($role)['image'];

        // Check if the image exists
        if (!$imageURL) {
            return new JsonResponse("Image not found", Response::HTTP_NOT_FOUND);
        }

        $skinImage = $this->getNationsGlorySkin($username);
        $color = $this->selectImage($role)['color'];
        $imagePath = 'img/tmp/skins/' . $username . '.png';
        $outputPath = 'img/tmp/rankup/';

        // Points to cut the skin (polygon)
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
            // Download the skin from the URL
            $this->downloadImage($skinImage, $imagePath, $points);

            // Replace the skin on the template
            $finalTemplate = $this->replaceSkinOnTemplate($username, $imagePath, $imageURL, $outputPath);

            // Draw the username within the rectangle
            $this->drawTextWithinRectangle($finalTemplate, $username, $outputPath);

            // Return OK
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse("An error occurred: " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Download the image from the URL and call the cutSkinToShape function
     * @param string $imageUrl The URL of the image
     * @param string $savePath The path to save the image
     * @param array $points The points to cut the skin (polygon)
     * @return void
     * @throws Exception
     */
    private function downloadImage(string $imageUrl, string $savePath, array $points): void
    {
        $imageContent = file_get_contents($imageUrl);

        // Check if the skin exists
        if ($imageContent === false) {
            throw new Exception("Failed to download image.");
        }

        $skin = new Imagick();

        $skin->readImageBlob($imageContent);

        // Cut the skin to the shape of the polygon
        $this->cutSkinToShape($skin, $points, $savePath);
    }

    /**
     * Select the image and the color based on the role
     * @param string $url The role
     * @return array The image and the color
     */
    private function selectImage(string $url): array
    {

        $images = [
            'ROLE_ADMIN' => ['image' => 'RankUpAdmin.png', 'color' => "#e82446"],
            'ROLE_SUPER_MOD' => ['image' => 'RankUpSM.png', 'color' => "#3b49c2"],
            'ROLE_MOD_PLUS' => ['image' => 'RankUpModPlus.png', 'color' => "#3e8d23"],
            'ROLE_MOD' => ['image' => 'RankUpMod.png', 'color' => '#7ac95f'],
            'ROLE_MOD_TEST' => ['image' => 'RankUpModTest.png', 'color' => '#a2e58b'],
            'ROLE_GUIDE' => ['image' => 'RankUpGuide.png', 'color' => "#ae6eee"],
            'ROLE_JOURNALIST' => ['image' => 'RankJournalist.png', 'color' => "#c80425"],
            'ROLE_ROLEPLAY' => ['image' => 'RankRoleplay.png', 'color' => "#c70425"],
            'ROLE_BUILDER' => ['image' => 'RankBuilder.png', 'color' => "#eb862a"],
            'DERANK_ROLEPLAY' => ['image' => 'DerankRoleplay.png', 'color' => "#c50424"],
            'DERANK_BUILDER' => ['image' => 'DerankBuilders.png', 'color' => "#c80425"],
            'DERANK_JOURNALIST' => ['image' => 'DerankJournalist.png', 'color' => "#c70727"],
            'DERANK_STAFF' => ['image' => 'DerankStaff.png', 'color' => "#c80425"],
        ];


        return $images[$url];
    }

    /**
     * Get the skin from the NationsGlory API
     * @param string $username The username
     * @return string The URL of the skin
     */
    private function getNationsGlorySkin(string $username): string
    {

        return "https://skins.nationsglory.fr/body/$username/3d/16";
    }

    /**
     * Replace the skin on the template
     * @param string $username The username
     * @param string $skinImagePath The path of the skin
     * @param string $templatePath The path of the template
     * @param string $outputPath The path to save the image
     * @return Imagick The template
     * @throws ImagickException
     */
    private function replaceSkinOnTemplate(string $username, string $skinImagePath, string $templatePath, string $outputPath): Imagick
    {
        $template = new Imagick($templatePath);
        $skin = new Imagick($skinImagePath);

        $skinWidth = 480;
        $skinHeight = 960;
        $skin->resizeImage($skinWidth, $skinHeight, Imagick::FILTER_LANCZOS, 1); // Resize the skin

        $x = 210;
        $y = 150;

        $template->compositeImage($skin, Imagick::COMPOSITE_OVER, $x, $y); // Replace the skin on the template

        $outputFilename = $username . '_rankup.png';
        $fullOutputPath = $outputPath . $outputFilename;
        $template->writeImage($fullOutputPath);

        $skin->clear();
        $skin->destroy();

        return $template;
    }

    /**
     * Draw the username within the rectangle
     * @param Imagick $image The template
     * @param string $username The username
     * @param string $outputPath The path to save the image
     * @return void
     * @throws ImagickDrawException
     * @throws ImagickException
     */
    private function drawTextWithinRectangle(Imagick $image, string $username, string $outputPath): void
    {
        [$x1, $y1, $x2, $y2] = $this->rectangleCoordinates;
        $rectangleWidth = $x2 - $x1;
        $rectangleHeight = $y2 - $y1;

        $draw = new ImagickDraw();
        $draw->setFillColor('white');
        $draw->setFont('font/OpenSans/static/OpenSans-ExtraBold.ttf');

        $fontSize = 10;
        $draw->setFontSize($fontSize);

        $metrics = $image->queryFontMetrics($draw, $username);

        // Increase the font size until the text fits within the rectangle
        while ($metrics['textWidth'] <= $rectangleWidth && $metrics['textHeight'] <= $rectangleHeight) {
            $fontSize++;
            $draw->setFontSize($fontSize);
            $metrics = $image->queryFontMetrics($draw, $username);
        }

        $fontSize--;
        $draw->setFontSize($fontSize);

        // Center the text within the rectangle
        $textX = $x1 + ($rectangleWidth - $metrics['textWidth']) / 2;
        $textY = $y1 + ($rectangleHeight - $metrics['textHeight']) / 2 + $metrics['ascender'];

        $draw->annotation($textX, $textY, $username); // Draw the username within the rectangle
        $image->drawImage($draw); // Draw the text on the image

        $draw->clear();
        $draw->destroy();

        $outputFilename = $username . '_rankup.png';
        $fullOutputPath = $outputPath . $outputFilename;
        $image->writeImage($fullOutputPath);

        $image->clear();
        $image->destroy();
    }

    /**
     * Cut the skin to the shape of the polygon
     * @param Imagick $skin The skin
     * @param array $pointsStringArray The points to cut the skin (polygon)
     * @param string $outputPath The path to save the image
     * @throws ImagickDrawException
     * @throws ImagickException
     * @return void
     */
    private function cutSkinToShape(Imagick $skin, array $pointsStringArray, string $outputPath): void
    {
        $mask = new Imagick();
        $mask->newImage($skin->getImageWidth(), $skin->getImageHeight(), 'transparent'); // Create a transparent mask
        $mask->setImageFormat('png');

        $draw = new ImagickDraw();
        $draw->setFillColor('black');

        $polygonPoints = $this->convertPointsToPolygon($pointsStringArray); // Convert the points to a polygon

        $draw->polygon($polygonPoints); // Draw the polygon
        $mask->drawImage($draw);

        $skin->compositeImage($mask, Imagick::COMPOSITE_DSTIN, 0, 0); // Cut the skin to the shape of the polygon

        $skin->writeImage($outputPath);

        $draw->clear();
        $draw->destroy();
        $mask->clear();
        $mask->destroy();
    }

    /**
     * Convert the points to a polygon
     * @param array $pointsStringArray The points to cut the skin (polygon)
     * @return array The points as a polygon
     */
    private function convertPointsToPolygon(array $pointsStringArray): array
    {
        $pointsArray = [];

        // Convert the points to an array
        foreach ($pointsStringArray as $pointString) {
            [$x, $y] = explode(';', $pointString);
            $pointsArray[] = ['x' => trim($x), 'y' => trim($y)];
        }
        return $pointsArray;
    }
}
