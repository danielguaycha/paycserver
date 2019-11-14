@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Editar Ruta
                </div>
                <div class="card-body">
                    <form action="{{ route('ruta.update', ['rutum' => $ruta->id ]) }}" method="post" >
                        {{ csrf_field() }}
                        @method('put')
                        <div class="form-group">
                            {{--- Nombres  ---}}
                            <label for="name">Nombre</label>
                            <input type="text"
                                   id="name"
                                   max="100"
                                   value="{{ old('name', $ruta->name) }}"
                                   required
                                   name="name" class="form-control">
                        </div>
                        <div class="form-group">
                            {{--- Decripción  ---}}
                            <label for="description">Descripción</label>
                            <input type="text"
                                   id="description"
                                   value="{{ old('description', $ruta->description) }}"
                                   max="100"
                                   required
                                   name="description" class="form-control">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">Actualizar Ruta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
