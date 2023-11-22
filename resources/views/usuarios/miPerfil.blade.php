@extends('principal.principal')

@section('body')

<nav class="">
    <ul>
        <li><h1>Mi Perfil</h1></li>
        <li>
            <!-- Dropdown -->
            <details role="list">
                <summary aria-haspopup="listbox">Dropdown</summary>
                <ul role="listbox">
                <li><a>Action</a></li>
                <li><a>Another action</a></li>
                <li><a>Something else here</a></li>
                </ul>
            </details>
        </li>
        <li>
            <!-- Select -->
            <select>
                <option value="" disabled selected>Select</option>
                <option>…</option>
            </select>
        </li>
    </ul>

</nav>

<article>
    <header>Mi Perfil</header>
    {{Auth::User()}}
</article>



</div>
    
@endsection

