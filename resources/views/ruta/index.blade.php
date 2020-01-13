@extends('layouts.app')


@section('content')

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        Rutas
                    </div>
                    @role('Admin')
                        <div>
                            <a href="{{ route('ruta.create') }}" class="btn btn-primary btn-sm">Nueva Ruta</a>
                        </div>
                    @endrole
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            @role('Admin')
                                <th>Opciones</th>
                            @endrole
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($rutas as $r)
                                <tr>
                                    <td>{{ $r->name }}</td>
                                    <td>{{ $r->description }}</td>
                                    @role('Admin')
                                    <td>
                                        <a href="{{ route('ruta.edit', ['rutum'=> $r->id ]) }}" class="btn btn-sm btn-primary">Editar</a>
                                        <form method="post" action="{{ route('ruta.destroy', ['rutum'=> $r->id ]) }}" id="form-{{ $r->id }}" class="d-inline-block">
                                            {{ csrf_field() }}
                                            @method('delete')
                                            <button type="button" onclick="confirmar(this)" class="btn btn-sm btn-danger">Eliminar</button>
                                        </form>
                                    </td>
                                    @endrole
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('js')
    <script>
        function confirmar(e) {
           if (!confirm('Estas seguro que deseas eliminar esta ruta?'))
            return;
            e.parentElement.submit();
        }
    </script>
@endsection
