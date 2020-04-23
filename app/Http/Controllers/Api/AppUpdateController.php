<?php

namespace App\Http\Controllers\Api;

use App\AppUpdate;
use App\Http\Controllers\ApiController;
use App\Traits\UploadTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;


class AppUpdateController extends ApiController
{

    use UploadTrait;

    public function __construct()
    {
        $this->middleware('auth:api')->except(['downloadUpdateTemp']);
        $this->middleware("root")->except(['getUpdate', 'downloadUpdate', 'last', 'downloadUpdateTemp']);
    }

    public function index(Request $request)
    {
        $limit = 30;
        if ($request->query('limit')) {$limit = $request->get('limit');}

        $up = AppUpdate::limit($limit)->get();

        return $this->showAll($up);
    }

    public function store(Request $request)
    {
        $request->validate([
            'build' => 'required|numeric',
            'version' => 'required|string',
            'description' => 'nullable|string|max:150',
            'src' => 'required|file'
        ]);

        if($request->src->getClientMimeType() !== 'application/vnd.android.package-archive') {
            return $this->err("El archivo debe ser formato .apk");
        }

        if ($this->existBuild($request->build)) {
            return $this->err("Ya existe el build con número ".$request->build);
        }

        $fileName = env("APK_NAME")."-".$request->version;

        $u = new AppUpdate();
        $u->src = $this->uploadOne($request->file('src'), '/apk', 'public', $fileName);

        if ($u === null || $u === '') {
            return $this->err("El archivo no pudo ser subido");
        }

        $u->build = $request->build;
        $u->version = $request->version;
        if ($request->has('description')) {$u->description = $request->description;}

        if ($u->save()) {
            return $this->success("Actualización subida correctamente");
        }

        return $this->err("Error al registrar la actualización");
    }

    public function update(Request $request, $id)
    {
        $up = AppUpdate::findOrFail($id);

        $request->validate([
            'build' => 'numeric',
            'version' => 'string',
            'description' => 'string|max:150',
            'src' => 'file'
        ]);

        if ($this->exitsBuildOnUpdate($request->build, $id)) {
            return $this->err("Ya existe el build con número ".$request->build);
        }

        if ($request->has('build'))
            $up->build = $request->build;
        if ($request->has('version'))
            $up->version = $request->version;
        if ($request->has('description'))
            $up->description = $request->description;

        if ($request->src) {
            if($request->src->getClientMimeType() !== 'application/vnd.android.package-archive') {
                return $this->err("El archivo debe ser formato .apk");
            }
            if (Storage::disk('public')->exists($up->src)) {
                Storage::disk('public')->delete($up->src);
            }
            $fileName = env("APK_NAME")."-".$up->version;
            $up->src = $this->uploadOne($request->file('src'), '/apk', 'public', $fileName);

            if ($up->src === null || $up->src === '') {
                return $this->err("No se ha podido actualizar la apk");
            }
        }

        if ($up->save()) {
            return $this->success("La actualización fué modificada con éxito");
        }

        return $this->err("Error al modificar la actualización");
    }

    public function destroy($id)
    {
        $up = AppUpdate::findOrFail($id);
        if (Storage::disk('public')->exists($up->src)) {
            Storage::disk('public')->delete($up->src);
        }

        if ($up->delete()) {
            return $this->success("Actualización eliminada");
        }
        return $this->err("Error al eliminar la actualización");
    }

    public function cancel($id) {
        $up = AppUpdate::findOrFail($id);
        $status = $up->status;
        if ($status == 1) {
            $up->status = 0;
        }
        if ($status == 0) {
            $up->status = 1;
        }
        $up->save();
        return $this->success("Estado cambiado a ".($up->status == 1 ? 'Activo': 'Inactivo'));
    }

    public function getUpdate(Request $request) {
        $request->validate([
            'build' => 'required|numeric',
        ]);

        $up = AppUpdate::where('build', '>', $request->build)
			->where('status', 1)
            ->orderBy('build', 'desc')->first();

        if(!$up) {
            $last = AppUpdate::where('status', 1)->orderBy('build', 'desc')->first();
            $date = null;
            if ($last) {
                $date = Carbon::parse($last->created_at)->format('Y-m-d');
            }
            return $this->custom([
                'ok' => true,
                'data'=> [
                    'update' => false,
                    'src' => null,
                    'last' => $date
                ]
            ]);
        } else {
            return $this->custom([
                'ok' => true,
                'data'=> [
                    'update' => true,
                    'build'=> $up->build,
                    'description' => $up->description,
                    'src' => $up->src,
					'version' => $up->version,
                    'last' => Carbon::parse($up->created_at)->format('Y-m-d')
                ]
            ]);
        }
    }

    public function downloadUpdate($path, $filename) {

        $path = storage_path('app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$filename);
        if (!File::exists($path)) {
            abort(404);
        }
        $name = basename($path);
        //$file = \Illuminate\Support\Facades\File::get($path);
        //$type = \Illuminate\Support\Facades\File::mimeType($path);

        //$response = Response::make($file, 200);
        //$response->header("Content-Type", $type);
        return Response::download($path, $name, [
            'Content-Length: '. filesize($path)
        ]);
    }

    public function downloadUpdateTemp($path, $filename, Request $request) {

        if (!$request->hasValidSignature()) {
            abort(404);
        }

        $path = storage_path('app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$filename);
        if (!File::exists($path)) {
            abort(404);
        }
        $name = basename($path);
        return Response::download($path, $name, [
            'Content-Length: '. filesize($path)
        ]);
    }

    public function last() {
        $up = AppUpdate::where('status', 1)->orderBy('build', 'desc')->first();
        if(!$up){
            return $this->err("No hay builds disponibles");
        }

        $url = URL::temporarySignedRoute(
            'updates.temp', now()->addMinutes(10), ['path' => 'apk', 'filename'=> Str::replaceFirst('apk/','', $up->src)]
        );

        return $this->data(['url' => $url]);
    }

    public function existBuild($build) {
        return AppUpdate::where('build', $build)->exists();
    }

    public function exitsBuildOnUpdate($build, $id) {
        return AppUpdate::where([
            ['build', $build],
            ['id', '<>', $id]
        ])->exists();
    }
}
