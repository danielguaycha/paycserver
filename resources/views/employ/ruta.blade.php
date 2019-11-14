@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Asignar rutas a empleados
                </div>
                <div class="card-body">
                    @if (!request()->query('employ'))
                    <form  method="get">
                        <div class="form-group">
                            <label for="">Seleccione empleado</label>
                            <select class="form-control" name="employ" id="employ">
                                @foreach($employs as $e)
                                    <option value="{{ $e->user_id }}"> {{ strtoupper($e->person->name) }} {{ strtoupper($e->person->surname) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mt-2">
                            <button type="submit" class="btn btn-primary">Seleccionar</button>
                        </div>
                    </form>
                    @endif

                    @if (request()->query('employ'))
                        <form action="{{ route('employ.store_route') }}" method="post">
                            {{ csrf_field() }}
                            <input type="hidden" name="user_id" readonly value="{{ $employs->user_id }}">
                            <div class="form-group">
                                <label for="user_name">Empleado</label>
                                <input type="text"
                                       class="form-control" name="user_name" id="user_name"
                                       value="{{ strtoupper($employs->person->name.' '.$employs->person->surname) }}"
                                       readonly>
                            </div>

                            @foreach($rutas as $r)
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input"
                                                   {{ $employs->user->rutas->pluck('id')->contains($r->id) ? 'checked': '' }}
                                                   type="checkbox" name="rutas[]"value="{{ $r->id }}">
                                            {{ $r->name }}
                                        </label>
                                    </div>
                                @endforeach
                            <hr>
                            <div class="form-group mt-2">
                                <button type="submit" class="btn btn-primary">Guardar asignación</button>
                                <a href="{{ route('employ.assign_route')}}" class="btn btn-danger">Atras</a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
