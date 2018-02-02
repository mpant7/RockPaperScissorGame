<?php
/**
 * Created by PhpStorm.
 * User: mpant
 * Date: 1/24/2018
 * Time: 12:52 PM
 */

namespace App\Controller;

use App\Entity\Games;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{

    /**
     * @Route("/")
     */
    public function defaultPage($errorMessage=""){
        $gameList = array();
        $em = $this->getDoctrine()->getManager();
        $games = $em -> getRepository(Games::class) -> findAll();
        foreach ($games as $elem) {
            $gameList[$elem ->getId()] = $elem ->getName();
        }

        return $this->render('gamePages/defaultPage.html.twig', [
            'gameList' => $gameList,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * @Route("/create-game-page")
     */
    public function createGamePage(Request $request, EntityManagerInterface $em){
        $gameName = $request->request->get('game-name');
        if ($gameName == null){
            return $this->defaultPage('Please enter the name');
        }
        $existingGame = $em->getRepository(Games::class)->findBy(['name' => $gameName]);
        if($existingGame){
            return $this->defaultPage('This name already exists. Enter a unique name');
        }

        try {
            $game = new Games();
            $game->setName($gameName);
            $game->setScore(0);
            $game->setTime(new \DateTime('Asia/Kolkata'));
            $em->persist($game);
            $em->flush();
        }catch (\PDOException $exception){
           return $this->defaultPage('Sorry! Game could not be created. Try again');
        }

        $selectedId = $game->getId();
        return $this->render('gamePages/createGamePage.html.twig', [
            'gameId' => $selectedId,
            'gameName' => $gameName,
        ]);
    }


    /**
     * @Route("/play-page")
     */
    public function playPage(Request $request, EntityManagerInterface $em) {
        $selectedID = $request->request->get('gameSelected');
        if($selectedID==null){
            return $this->defaultPage("Start your game from here");
        }
        try{
            $game = $em -> getRepository(Games::class) -> find($selectedID);
            $gameName = $game->getName();
            $gameScore = $game->getScore();
        } catch (\Exception $exception){
            return $this->defaultPage('Sorry! Some error in fetching your game. Try again.');
        }
        return $this->render('gamePages/playPage.html.twig', [
            'gameId' => $selectedID,
            'gameName' => $gameName,
            'currentScore' => $gameScore,
        ]);
    }

    /**
     * @Route("/result-page/{slug}")
     */
    public function resultPage($slug, Request $request, EntityManagerInterface $em) {
        $selectedId = $request->request->get('gameSelected');
        if($selectedId==null){
            return $this->defaultPage("Start your game from here");
        }
        try{
            $game = $em -> getRepository(Games::class) -> find($selectedId);
            $currentScore = $game->getScore();
        } catch (\Exception $exception){
            return $this->defaultPage('Sorry! Some error in fetching your game. Try again.');
        }

        // SETTING GAME RESULTS
        $randNum = mt_rand(0,2);
        $choices = array('ROCK','PAPER', 'SCISSOR');
        $systemChoice = $choices[$randNum];
        $gameResult = $this->gameLogic($slug, $systemChoice);

        // SETTING GAME SCORES
        $newScore = $currentScore;
        if ($gameResult=='WIN') {
            $newScore++;
        }
        elseif ($gameResult=='LOSE') {
            $newScore--;
        }
        $game -> setScore($newScore);
        try{
            $em -> flush();
        } catch (\PDOException $exception){
            return $this->defaultPage('Sorry! Some error in saving your game results. Try again');
        }

        return $this->render('gamePages/resultPage.html.twig', [
            'userPick' => $slug,
            'computerPick' => $systemChoice,
            'gameResult' => $gameResult,
            'newScore' => $newScore,
            'gameId' => $selectedId,
        ]);
    }

    public function gameLogic($userChoice, $systemChoice) {
        $resultOptions = array('WIN', 'LOSE', 'TIE', 'NIL');
        $gameResult = $resultOptions[3];

        if ( $userChoice==$systemChoice ){
            $gameResult = $resultOptions[2];
        }
        elseif ($userChoice=='ROCK' && $systemChoice=='PAPER') {
            $gameResult = $resultOptions[1];
        }
        elseif ($userChoice=='ROCK' && $systemChoice=='SCISSOR') {
            $gameResult = $resultOptions[0];
        }
        elseif ($userChoice=='PAPER' && $systemChoice=='SCISSOR') {
            $gameResult = $resultOptions[1];
        }
        elseif ($userChoice=='PAPER' && $systemChoice=='ROCK') {
            $gameResult = $resultOptions[0];
        }
        elseif ($userChoice=='SCISSOR' && $systemChoice=='PAPER') {
            $gameResult = $resultOptions[0];
        }
        elseif ($userChoice=='SCISSOR' && $systemChoice=='ROCK') {
            $gameResult = $resultOptions[1];
        }

        return $gameResult;
    }

    /**
     * @Route("/watch-page")
     */
    public function watchPage(EntityManagerInterface $em) {
        $games = $em -> getRepository(Games::class) -> findAll();
        $gameList = array();

        foreach ($games as $elem) {
            //$gameList[$elem ->getName()] = $elem ->getScore();
            $playerData = [$elem->getName(), $elem->getScore(), $elem->getTime()->format('Y-m-d H:i:s')];
            array_push($gameList, $playerData);
        }
        return $this->render('gamePages/watchPage.html.twig', [
            'gameList' => $gameList,
        ]);

    }

    /**
     * @Route("/{token}", name="wrongToken", requirements={"token"=".+"})
     */
    public function wrongTokens()    {
        return $this->defaultPage();
    }

}