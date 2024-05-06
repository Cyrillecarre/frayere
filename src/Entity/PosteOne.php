<?php

namespace App\Entity;

use App\Repository\PosteOneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PosteOneRepository::class)]
class PosteOne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $end = null;

    #[ORM\Column]
    private ?bool $isApprouved = false;

    #[ORM\Column(length: 7)]
    private ?string $background_color = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $phoneNumber = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(\DateTimeInterface $end): static
    {
        $this->end = $end;

        return $this;
    }

    public function isApprouved(): bool
    {
        return $this->isApprouved;
    }

    public function setApprouved(bool $isApprouved): self
    {
        $this->isApprouved = $isApprouved;
        if ($isApprouved) {
            switch (true) {
                case $this instanceof PosteOne:
                    $this->background_color = '#008000'; // Vert
                    break;
                case $this instanceof PosteTwo:
                    $this->background_color = '#0000FF'; // Bleu
                    break;
                case $this instanceof PosteThree:
                    $this->background_color = '#FF0000'; // Rouge
                    break;
                case $this instanceof PosteFour:
                    $this->background_color = '#FF1493'; // Rose
                    break;
                default:
                    $this->background_color = '#FF7F00'; // Orange par défaut
            }
        } else {
            $this->background_color = '#FF7F00'; // Orange lorsque non approuvé
        }

        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        
        return $this->background_color;
    }

    public function setBackgroundColor(string $background_color): static
    {
        $this->background_color = $background_color;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }
}
