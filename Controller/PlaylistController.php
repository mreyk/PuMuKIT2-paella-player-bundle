<?php

namespace Pumukit\PaellaPlayerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\BasePlayerBundle\Controller\BasePlaylistController;

class PlaylistController extends BasePlaylistController
{
    /**
     * @Route("/playlist/{id}", name="pumukit_playlistplayer_index", defaults={"no_channels": true} )
     * @Route("/playlist/magic/{secret}", name="pumukit_playlistplayer_magicindex", defaults={"show_hide": true, "no_channels": true} )
     *
     * Added default indexAction and redirect to the paella route.
     */
    public function indexAction(Series $series, Request $request)
    {
        $mmobjId = $request->get('videoId');
        return $this->redirectWithMmobj($series, $request, $mmobjId);
    }

    /**
     * @Route("/playlist", name="pumukit_playlistplayer_paellaindex", defaults={"no_channels": true} )
     * @Template("PumukitPaellaPlayerBundle:PaellaPlayer:player.html.twig")
     *
     * In order to make things easier on the paella side, we drop the symfony custom urls.
     */
    public function paellaIndexAction(Request $request)
    {
        $mmobjId = $request->get('videoId');
        $seriesId = $request->get('playlistId');

        $series = $this->get('doctrine_mongodb.odm.document_manager')
                       ->getRepository('PumukitSchemaBundle:Series')
                       ->find($seriesId);
        if(!$series){
            throw $this->createNotFoundException("Not series found with id: $seriesId");
        }

        if(!$mmobjId) {
            //If the player has no mmobjId, we should provide it ourselves.
            return $this->redirectWithMmobj($series, $request);
        }

        $playlistService = $this->get('pumukit_baseplayer.seriesplaylist');
        $mmobj = $playlistService->getMmobjFromIdAndPlaylist($mmobjId, $series);

        if(!$mmobj)
            throw $this->createNotFoundException("Not mmobj found with the id: $mmobjId as part of the series with id: $seriesId");

        return array(
            'autostart' => $request->query->get('autostart', 'false'),
            'object' => $series,
            'multimediaObject' => $mmobj,
            'responsive' => true,
        );
    }

    /**
     * Helper function to used to redirect when the mmobj id is not specified in the request.
     */
    private function redirectWithMmobj(Series $series, Request $request, $mmobjId = null)
    {
        $playlistService = $this->get('pumukit_baseplayer.seriesplaylist');
        if(!$mmobjId) {
            $mmobj = $playlistService->getPlaylistFirstMmobj($series);
            if(!$mmobj)
                throw $this->createNotFoundException("Not mmobj found for the playlist with id: {$series->getId()}");
            $mmobjId = $mmobj->getId();
        }

        $redirectUrl = $this->generateUrl(
            'pumukit_playlistplayer_paellaindex',
            array(
                'playlistId' => $series->getId(),
                'videoId' => $mmobjId,
                'autostart' => $request->query->get('autostart', 'false'),
            )
        );
        return $this->redirect($redirectUrl);
    }
}
