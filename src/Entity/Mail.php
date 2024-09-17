<?php

namespace App\Entity;

use App\Repository\MailRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MailRepository::class)]
class Mail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $object = null;

    #[ORM\Column(length: 255)]
    private ?string $text = null;

    #[ORM\Column(length: 255)]
    private ?string $dest_principal = null;

    #[ORM\Column(length: 255)]
    private ?string $dest_copie = null;


    /**
     * @ORM\OneToOne(targetEntity=Dossier::class, inversedBy="mail")
     */
    private $dossier;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObject(): ?string
    {
        return $this->object;
    }

    public function setObject(string $object): static
    {
        $this->object = $object;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getDestPrincipal(): ?string
    {
        return $this->dest_principal;
    }

    public function setDestPrincipal(string $dest_principal): static
    {
        $this->dest_principal = $dest_principal;

        return $this;
    }

    public function getDestCopie(): ?string
    {
        return $this->dest_copie;
    }

    public function setDestCopie(string $dest_copie): static
    {
        $this->dest_copie = $dest_copie;

        return $this;
    }




}
