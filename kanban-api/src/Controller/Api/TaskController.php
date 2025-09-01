<?php
namespace App\Controller\Api;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface as EM;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tasks')]
class TaskController
{
    public function __construct(private EM $em) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse {
        $repo = $this->em->getRepository(Task::class);
        $rows = $repo->createQueryBuilder('t')
            ->orderBy('t.status', 'ASC')
            ->addOrderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()->getArrayResult();

        $rows = array_map(fn($r)=>[
            'id'=>$r['id'],
            'title'=>$r['title'],
            'status'=>$r['status'],
            'sort_order'=>$r['sortOrder'],
            'created_at'=>($r['createdAt']? $r['createdAt']->format('c'): null),
        ], $rows);

        return new JsonResponse($rows);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $req): JsonResponse {
        $data = json_decode($req->getContent(), true) ?? [];
        $title = trim($data['title'] ?? '');
        $status = $data['status'] ?? 'todo';
        if ($title === '') return new JsonResponse(['error'=>'Title required'], 400);

        $max = $this->em->createQuery(
            'SELECT COALESCE(MAX(t.sortOrder), -1) FROM App\Entity\Task t WHERE t.status = :s'
        )->setParameter('s', $status)->getSingleScalarResult();

        $next = ((int) $max) + 1;

        $task = (new Task())
            ->setTitle($title)
            ->setStatus($status)
            ->setSortOrder($next)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($task);
        $this->em->flush();

        return new JsonResponse([
            'id'=>$task->getId(),
            'title'=>$task->getTitle(),
            'status'=>$task->getStatus(),
            'sort_order'=>$task->getSortOrder(),
            'created_at'=>$task->getCreatedAt()->format('c')
        ], 201);
    }

    #[Route('/{id<\\d+>}', methods: ['PUT'])]
    public function update(int $id, Request $req): JsonResponse {
        $task = $this->em->find(Task::class, $id);
        if (!$task) return new JsonResponse(['error'=>'Not found'], 404);

        $data = json_decode($req->getContent(), true) ?? [];
        if (isset($data['title'])) $task->setTitle($data['title']);
        if (isset($data['status'])) $task->setStatus($data['status']);
        if (isset($data['sort_order'])) $task->setSortOrder((int)$data['sort_order']);
        $this->em->flush();

        return new JsonResponse([
            'id'=>$task->getId(),
            'title'=>$task->getTitle(),
            'status'=>$task->getStatus(),
            'sort_order'=>$task->getSortOrder(),
            'created_at'=>$task->getCreatedAt()->format('c')
        ]);
    }

    #[Route('/{id<\\d+>}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse {
        $task = $this->em->find(Task::class, $id);
        if ($task) { $this->em->remove($task); $this->em->flush(); }
        return new JsonResponse(['ok'=>true]);
    }

    #[Route('/reorder', methods: ['PATCH'])]
    public function reorder(Request $req): JsonResponse {
        $data = json_decode($req->getContent(), true) ?? [];
        $status  = $data['status'] ?? null;
        $ordered = $data['orderedIds'] ?? null;
        if (!$status || !\is_array($ordered)) return new JsonResponse(['error'=>'Bad payload'], 400);

        $this->em->beginTransaction();
        try {
            foreach ($ordered as $i => $taskId) {
                $task = $this->em->find(Task::class, (int)$taskId);
                if ($task) { $task->setStatus($status)->setSortOrder($i); }
            }
            $this->em->flush();
            $this->em->commit();
            return new JsonResponse(['ok'=>true]);
        } catch (\Throwable $e) {
            $this->em->rollback();
            return new JsonResponse(['error'=>'Reorder failed','message'=>$e->getMessage()], 500);
        }
    }
}
