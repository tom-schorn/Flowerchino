<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/v1/auth')]
class AuthController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $constraints = new Assert\Collection([
            'email'     => [new Assert\NotBlank(), new Assert\Email()],
            'username'  => [new Assert\NotBlank(), new Assert\Length(min: 3, max: 60)],
            'password'  => [new Assert\NotBlank(), new Assert\Length(min: 8)],
            'firstName' => new Assert\Optional([new Assert\Length(max: 100)]),
            'lastName'  => new Assert\Optional([new Assert\Length(max: 100)]),
        ]);

        $violations = $validator->validate($data ?? [], $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = $v->getPropertyPath().': '.$v->getMessage();
            }

            return $this->json(['status' => 'error', 'error' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($em->getRepository(User::class)->findOneBy(['email' => $data['email']])) {
            return $this->json(['status' => 'error', 'error' => 'Email already in use.'], Response::HTTP_CONFLICT);
        }

        if ($em->getRepository(User::class)->findOneBy(['username' => $data['username']])) {
            return $this->json(['status' => 'error', 'error' => 'Username already taken.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setFirstName($data['firstName'] ?? null);
        $user->setLastName($data['lastName'] ?? null);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'data'   => [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'username'  => $user->getUsername(),
            ],
        ], Response::HTTP_CREATED);
    }
}
