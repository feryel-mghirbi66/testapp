<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use App\Repository\MedicamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCommande = null;



    #[ORM\Column]
    private ?float $total_prix = null;

    /**
     * @var Collection<int, Medicament>
     */
    #[ORM\ManyToMany(targetEntity: Medicament::class, inversedBy: 'commande_medicament')]
    #[ORM\JoinTable(name: 'commande_medicament')]
    private Collection $medicaments;

    #[ORM\Column(length: 255, options: ['default' => 'pending'])]
    private ?string $status = 'pending';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    
    
    #[ORM\Column]
    private ?float $quantite = null;

    public function __construct()
    {
        $this->medicaments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeInterface $dateCommande): static
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }



    public function getTotalPrix(): ?float
    {
        return $this->total_prix;
    }

    public function setTotalPrix(float $total_prix): static
    {
        $this->total_prix = $total_prix;

        return $this;
    }

    /**
     * @return Collection<int, Medicament>
     */
    public function getMedicaments(): Collection
    {
        return $this->medicaments;
    }

    public function addMedicament(Medicament $medicament): static
    {
        if (!$this->medicaments->contains($medicament)) {
            $this->medicaments->add($medicament);
            $medicament->addCommandeMedicament($this);
        }

        return $this;
    }

    public function removeMedicament(Medicament $medicament): static
    {
        if ($this->medicaments->removeElement($medicament)) {
            $medicament->removeCommandeMedicament($this);
        }

        return $this;
    }

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(float $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }



    public function getStatus(): ?string
    {
    return $this->status;
    }

    public function setStatus(string $status): static
    {
    $this->status = $status;
    return $this;
    }

    public function getStripeSessionId(): ?string
    {
    return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
    $this->stripeSessionId = $stripeSessionId;
    return $this;
    }
}
