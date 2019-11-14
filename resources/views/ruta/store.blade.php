@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Registrar Ruta
                </div>
                <div class="card-body">
                    <form action="{{ route('ruta.store') }}" method="post" >
                        {{ csrf_field() }}
                        <div class="form-group">
                            {{--- Nombres  ---}}
                            <label for="name">Nombre</label>
                            <input type="text"
                                   id="name"
                                   max="100"
                                   required
                                   name="name" class="form-control">
                        </div>
                        <div class="form-group">
                            {{--- Decripción  ---}}
                            <label for="description">Descripción</label>
                            <input type="text"
                                   id="description"
                                   max="100"
                                   required
                                   name="description" class="form-control">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Guardar Ruta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
