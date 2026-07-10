<?php
require_once __DIR__ . '/../models/ArticuloModel.php';

class ArticuloController {
    private $m;
    public function __construct() { $this->m = new ArticuloModel(); }

    private function res($v, $m, $d=null, $c=200) {
        http_response_code($c); header('Content-Type: application/json');
        echo json_encode(['value'=>$v,'message'=>$m,'data'=>$d]); exit;
    }

    private function upload() {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['foto']['size'] > 1048576) {
                throw new Exception('La foto no debe superar 1 MB.');
            }
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $name = uniqid() . '.' . $ext;
            $dir = __DIR__ . '/../uploads/articulos/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $name)) {
                return 'uploads/articulos/' . $name;
            }
        }
        return null;
    }

    public function listar() {
        try {
            $empresa_id = $_GET['empresa_id'] ?? null;
            $this->res(true, 'OK', $this->m->listar($empresa_id));
        }
        catch (Exception $e) { $this->res(false, $e->getMessage(), null, 500); }
    }
    public function crear() {
        $d = $_POST;
        if (empty($d)) $d = json_decode(file_get_contents('php://input'), true) ?: [];
        $foto = $this->upload();
        if ($foto) $d['foto'] = $foto;
        $this->res(true, 'OK', ['id' => $this->m->crear($d)], 201);
    }
    public function actualizar($p) {
        $id = $p['id'] ?? '';
        $d = $_POST;
        if (empty($d)) $d = json_decode(file_get_contents('php://input'), true) ?: [];
        $foto = $this->upload();
        if ($foto) $d['foto'] = $foto;
        if (!$foto && !empty($d['foto_actual'])) $d['foto'] = $d['foto_actual'];
        $this->res(true, 'OK', ['ok' => $this->m->actualizar($id, $d)]);
    }
    public function eliminar($p) {
        if (!($id = $p['id'] ?? '')) $this->res(false, 'ID req', null, 400);
        try { $this->res(true, 'OK', ['ok' => $this->m->eliminar($id)]); }
        catch (Exception $e) { $this->res(false, $e->getMessage(), null, 500); }
    }
}