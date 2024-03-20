<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }


    public function load(ObjectManager $manager): void
    {
        $category = new Category();
        $category->setName('Calendar');
        $category->setDescription('Display a calendar of events in the Ruby');
        $category->setColor('#FF5733');

        $category2 = new Category();
        $category2->setName('Events');
        $category2->setDescription('Display a list of events in the Ruby');
        $category2->setColor('#008000');

        $category3 = new Category();
        $category3->setName('UN');
        $category3->setDescription('What appended during the last UN meeting');
        $category3->setColor('#87CEEB');

        $category4 = new Category();
        $category4->setName('Wiki');
        $category4->setDescription('How to play in the Ruby');
        $category4->setColor('#FFD700');

        $user = new User();
        $user->setUsername("Armotik");
        $user->setPassword($this->passwordHasher->hashPassword($user, "admin"));
        $user->setRoles(['ROLE_MOD', 'ROLE_WEBMASTER']);
        $user->setApiToken("ArmotikToken");

        $manager->persist($category);
        $manager->persist($category2);
        $manager->persist($category3);
        $manager->persist($category4);

        $manager->persist($user);

        $manager->flush();
    }
}
