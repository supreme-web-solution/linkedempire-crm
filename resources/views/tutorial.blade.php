@extends('layout.auth')

@section('content')
<h2 class="text-lg font-semibold text-gray-900">
    Tutorials
</h2>
<div class="min-w-0 p-6 bg-white rounded-lg shadow-xs">
    <div class="grid gap-6 mb-8 grid-cols-6 md:grid-cols-12 tutorials-card"></div>
</div>

<script>
    const tutorials = [
        // {
        //     link: 'https://vimeo.com/922520774', 
        //     thumbnail: 'https://i.vimeocdn.com/video/1813607709-0610e691cda5e0fce1a5780c0f582b7a781c00f6a2b04957860effafb1f1877b-d_640x360',
        //     label: 'Audience Creation'
        // },
        // {
        //     link: 'https://vimeo.com/922517282', 
        //     thumbnail: 'https://i.vimeocdn.com/video/1813601760-4fe017ab336c1e1a8e5d74ba979ad0cf24c874f33b33bd2a7ce0386613ae16ea-d_640x360',
        //     label: 'Add Connections'
        // },
        // {
        //     link: 'https://vimeo.com/922522868', 
        //     thumbnail: 'https://i.vimeocdn.com/video/1813607709-0610e691cda5e0fce1a5780c0f582b7a781c00f6a2b04957860effafb1f1877b-d_640x360',
        //     label: 'Auto-respond Message'
        // },
        // {
        //     link: 'https://vimeo.com/922524478', 
        //     thumbnail: 'https://i.vimeocdn.com/video/1813611206-0c3253a635a4b5eae91a876f495b3c0a294c1c40bdd4de2a3dff06e9923208ed-d_640x360',
        //     label: 'Follow up Message'
        // },
        // {
        //     link: 'https://vimeo.com/922525264', 
        //     thumbnail: 'https://i.vimeocdn.com/video/1813614009-16783ad5a67367b5072147dc400f1c5eec719426f5be7cf129d618a7552ab644-d_640x360',
        //     label: 'Message Targeted Users'
        // },
        // {
        //     link: 'https://vimeo.com/922527093', 
        //     thumbnail: 'https://i.vimeocdn.com/video/1813614396-eb678539312c9c89365cfa26a1524d5487653a1e316db2b6e1e06848609b98ee-d_640x360',
        //     label: 'Schedule Post'
        // },
      
        // {
        //     link: 'https://vimeo.com/1000870801', 
        //     thumbnail: 'https://i.vimeocdn.com/video/1917028869-6a757dd5e2ebe1b42105a56c3417b29de436e4f6941cf1e07887c0df72edbf57-d_640x360',
        //     label: 'Campaign'
        // },

        
        {
            link: 'https://vimeo.com/1165389320', 
            thumbnail: 'https://i.vimeocdn.com/video/1917028869-6a757dd5e2ebe1b42105a56c3417b29de436e4f6941cf1e07887c0df72edbf57-d_640x360',
            label: 'IMPORTANT: Watch This First to Get Started the Right Way'
        },
        {
            link: 'https://vimeo.com/1165390082', 
            thumbnail: 'https://i.vimeocdn.com/video/1917028869-6a757dd5e2ebe1b42105a56c3417b29de436e4f6941cf1e07887c0df72edbf57-d_640x360',
            label: 'Competitor Active Followers'
        },
        {
            link: 'https://vimeo.com/1165390293', 
            thumbnail: 'https://i.vimeocdn.com/video/1917028869-6a757dd5e2ebe1b42105a56c3417b29de436e4f6941cf1e07887c0df72edbf57-d_640x360',
            label: 'AI Content Creation'
        },
        {
            link: 'https://vimeo.com/1165391485', 
            thumbnail: 'https://i.vimeocdn.com/video/1917028869-6a757dd5e2ebe1b42105a56c3417b29de436e4f6941cf1e07887c0df72edbf57-d_640x360',
            label: 'Campaign'
        },
        {
            link: 'https://vimeo.com/1165390834', 
            thumbnail: 'https://i.vimeocdn.com/video/1917028869-6a757dd5e2ebe1b42105a56c3417b29de436e4f6941cf1e07887c0df72edbf57-d_640x360',
            label: 'Leads & AI Messages'
        },
        {
            link: 'https://mega.nz/file/kXcSTCzL#Kdn7OU8pRq0er_Bm1KOYj2TzeH1uE3eejrcl0p_q-Vs', 
            thumbnail: 'https://i.vimeocdn.com/video/1917028869-6a757dd5e2ebe1b42105a56c3417b29de436e4f6941cf1e07887c0df72edbf57-d_640x360',
            label: 'Extension Features'
        },
    ];

    let display = ''
    $.each(tutorials, function(i, item) {
        display = `
        <div class="col-span-3 border border-gray-400 shadow-md rounded">
            <a class="fancy-box" href="${item.link}">
                <img src="${item.thumbnail}" alt="${item.label}">
            </a>
            <p class="font-medium mt-4 pl-2 pb-2 whitespace-wrap">${item.label}</p>
        </div>
        `;
        $('.tutorials-card').append(display)
    })
    $("a.fancy-box").fancybox();
</script>
@endsection