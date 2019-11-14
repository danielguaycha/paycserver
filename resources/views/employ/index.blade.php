@extends('layouts.app')

@section('content')

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        Empleados
                    </div>
                    <div>
                        <a href="{{ route('employ.create') }}" class="btn btn-primary btn-sm">Nueva Empleado</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Sueldo Base</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th class="text-right">Opciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($employs as $r)
                                <tr>
                                    <td>{{ strtoupper($r->name) }} {{ strtoupper($r->surname) }}</td>
                                    <td>{{ $r->username }}</td>
                                    <td>{{ number_format($r->sueldo, 2) }}</td>
                                    <td>{{ $r->phones }}</td>
                                    <td>
                                        @if($r->status === 1)
                                            <span class="badge badge-success">
                                                Activo
                                            </span>
                                        @else
                                            <span class="badge badge-danger">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="d-flex justify-content-end">
                                        <a href="{{ route('employ.edit', ['employ'=> $r->id ]) }}" class="btn btn-sm btn-primary">Editar</a>
                                        <form method="post" action="{{ route('employ.destroy', ['employ'=> $r->id ]) }}" id="form-{{ $r->id }}" class="d-inline-block">
                                            {{ csrf_field() }}
                                            @method('delete')
                                            <button type="button" onclick="confirmar(this)" class="btn btn-sm btn-danger">Eliminar</button>
                                        </form>
                                        <div class="dropdown">
                                            <button class="btn btn-secondary dropdown-toggle btn-sm" type="button" id="e-{{ $r->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                +
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="e-{{ $r->id }}">
                                                <a class="dropdown-item" href="{{ route('employ.assign_route', ['employ'=> $r->user_id]) }}">Asignar ruta</a>
                                                <form action="{{ route('employ.cancel', ['id'=> $r->id ]) }}" method="post">
                                                    @method('put')
                                                    @csrf
                                                    <button class="dropdown-item">{{ ($r->status === 1 ) ? 'Dar de baja':'Dar de alta' }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $employs->links() }}
                </div>
            </div>
        </div>
    </div>

@stop

@section('js')
    <script>
        function confirmar(e) {
            if (!confirm('Estas seguro que deseas eliminar este empleado?'))
                return;
            e.parentElement.submit();
        }
    </script>
@endsection
