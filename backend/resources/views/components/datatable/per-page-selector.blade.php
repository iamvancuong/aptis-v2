@props(['options' => [10, 20, 50], 'current' => 10])

<form method="GET" class="flex items-center space-x-2">
    <!-- Preserve existing query parameters -->
    @foreach(request()->except(['per_page', 'page']) as $key => $value)
        @if(is_array($value))
            @foreach($value as $item)
                <input type="hidden" name="{{$key}}[]" value="{{$item}}">
            @endforeach
        @else
            <input type="hidden" name="{{$key}}" value="{{$value}}">
        @endif
    @endforeach
    
    <label for="per_page" class="text-sm text-gray-600 whitespace-nowrap">Items per page:</label>
    <select 
        id="per_page" 
        name="per_page" 
        class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
        onchange="this.form.submit()"
    >
        @foreach($options as $option)
            <option value="{{$option}}" @selected($current == $option)>
                {{$option}}
            </option>
        @endforeach
    </select>
</form>
