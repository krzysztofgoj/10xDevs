<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\Serializer\Attribute\Groups;

final class PaginatedResponse
{
    #[Groups(['api'])]
    public array $data;

    #[Groups(['api'])]
    public int $page;

    #[Groups(['api'])]
    public int $limit;

    #[Groups(['api'])]
    public int $total;

    #[Groups(['api'])]
    public int $totalPages;

    public function __construct(
        array $data,
        int $page,
        int $limit,
        int $total
    ) {
        $this->data = $data;
        $this->page = $page;
        $this->limit = $limit;
        $this->total = $total;
        $this->totalPages = (int) ceil($total / $limit);
    }
}

