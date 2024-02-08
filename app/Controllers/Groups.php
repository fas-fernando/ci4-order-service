<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Entities\Group;
use CodeIgniter\HTTP\ResponseInterface;

class Groups extends BaseController
{
    private $groupModel;

    public function __construct()
    {
        $this->groupModel = model('App\Models\GroupModel');
    }

    public function index()
    {
        $data = [
            'title' => 'Lista de Grupos',
        ];

        return view('Groups/index', $data);
    }

    public function getGroups()
    {
        if (!$this->request->isAJAX()) return redirect()->back();

        $attr = [
            'id',
            'name',
            'description',
            'show',
            'deleted_at',
        ];

        $groups = $this->groupModel->select($attr)->withDeleted()->orderBy('id', 'DESC')->findAll();

        $data = [];

        foreach ($groups as $group) {

            $name = esc($group->name);

            $data[] = [
                'name'        => anchor("groups/show/$group->id", $name, 'title="Exibir grupo ' . $name . '"'),
                'description' => esc($group->description),
                'show'        => $group->showSituation(),
            ];
        }

        $returnData = [
            'data' => $data,
        ];

        return $this->response->setJSON($returnData);
    }

    public function show(int $id = null)
    {
        $group = $this->getGroupOr404($id);

        $data = [
            'title' => 'Info do grupo ' . esc($group->name),
            'group' => $group,
        ];

        return view("Groups/show", $data);
    }

    public function edit(int $id = null)
    {
        $group = $this->getGroupOr404($id);

        if ($group->id < 3)
            return redirect()
                ->back()
                ->with('attention', 'O grupo ' . esc($group->name) . ' não pode ser editado ou excluído');

        $data = [
            'title' => 'Editar grupo ' . esc($group->name),
            'group' => $group,
        ];

        return view("Groups/edit", $data);
    }

    public function update()
    {
        if (!$this->request->isAJAX()) return redirect()->back();

        $returnData['token'] = csrf_hash();

        $post = $this->request->getPost();

        $group = $this->getGroupOr404($post['id']);

        if ($group->id < 3) {
            $returnData['error'] = 'Por favor, verifique os erros abaixo e tente novamente';
            $returnData['errors_model'] = ['group' => 'O grupo <strong class="text-white">' . esc($group->name) . '</strong> não pode ser editado ou excluído'];

            return $this->response->setJSON($returnData);
        }

        $group->fill($post);

        if ($group->hasChanged() == false) {
            $returnData['info'] = 'Não há dados para serem atualizados';
            return $this->response->setJSON($returnData);
        }

        if ($this->groupModel->save($group)) {
            session()->setFlashdata('success', 'Dados salvos com sucesso');

            return $this->response->setJSON($returnData);
        }

        $returnData['error'] = 'Por favor, verifique os erros abaixo e tente novamente';
        $returnData['errors_model'] = $this->groupModel->errors();

        return $this->response->setJSON($returnData);
    }

    private function getGroupOr404(int $id = null)
    {
        $group = $this->groupModel->withDeleted(true)->find($id);

        if (!$id || !$group) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Não encontramos o grupo $id");
        }

        return $group;
    }
}
