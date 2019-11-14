@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Registrar empleado
                </div>
                <div class="card-body">
                    <form action="{{ route('employ.store') }}" method="post">
                        {{ csrf_field() }}
                        <div class="form-group form-row">
                            {{--- Nombres y apellidos ---}}
                            <div class="col">
                                <label for="name">Nombre</label>
                                <input type="text"
                                       id="name"
                                       max="100"
                                       required
                                       name="name" class="form-control">
                            </div>
                            <div class="col">
                                <label for="surname">Apellido</label>
                                <input type="text"
                                       id="surname"
                                       max="100"
                                       required
                                       name="surname" class="form-control">
                            </div>
                        </div>
                        <div class="form-group form-row">
                            {{--- Telefono y Dirección  ---}}
                            <div class="col-md-5">
                                <label for="phones">Teléfono/Celular</label>
                                <input type="text"
                                       id="phones"
                                       required
                                       name="phones" class="form-control">
                            </div>
                            <div class="col-md-7">
                                <label for="address">Dirección</label>
                                <input type="text"
                                       id="address"
                                       max="100"
                                       required
                                       name="address" class="form-control">
                            </div>
                        </div>
                        <div class="form-group form-row">
                            {{--- Email y Sueldo Base  ---}}
                            <div class="col-md-5">
                                <label for="email">Email</label>
                                <input type="email"
                                       id="email"
                                       name="email" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="sueldo">Sueldo Base</label>
                                <input type="number"
                                       id="sueldo"
                                       required
                                       name="sueldo" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="sueldo">Periodo de Pago</label>
                                <select name="pago_sueldo" id="pago_sueldo" class="form-control">
                                    @foreach($plazos as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <h6>Credenciales</h6>
                        <hr>
                        <div class="form-group form-row">
                            {{--- Usuario y Contraseña ---}}
                            <div class="col">
                                <label for="username">Usuario</label>
                                <input type="text"
                                       id="username"
                                       max="100"
                                       required
                                       name="username" class="form-control">
                            </div>
                            <div class="col">
                                <label for="password">Contraseña </label>
                                <div class="d-flex">
                                    <input type="password"
                                           id="password"
                                           max="100"
                                           required
                                           name="password" class="form-control">
                                    <button type="button" onclick="viewPassword()" class="btn btn-success">Ver</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Guardar Empleado</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function suggest(){

        }

        function viewPassword() {
            let pw = document.getElementById("password");
            if (pw.type === "password") {
                pw.type = "text";
            } else {
                pw.type = "password";
            }

        }
    </script>
@endsection
