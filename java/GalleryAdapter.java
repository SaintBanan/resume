package com.example.bt.adapters;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.example.bt.R;
import com.example.bt.fragments.main.deal.GalleryFragment;
import com.example.bt.model.DealImage;
import com.example.bt.modules.Helper;
import com.example.bt.modules.Image;

import java.util.ArrayList;
import java.util.List;

public class GalleryAdapter extends RecyclerView.Adapter<GalleryAdapter.ViewHolder> {

    private final Context context;
    private final GalleryFragment fragment;
    private List<DealImage> images;
    private final int space;

    public GalleryAdapter(Context context, GalleryFragment fragment) {
        this.context = context;
        this.fragment = fragment;
        this.images = new ArrayList<>();
        this.space = Helper.dpToPx(context, 1);
    }

    @Override
    public int getItemViewType(int position) {
        return position;
    }

    @NonNull
    @Override
    public GalleryAdapter.ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        return new GalleryAdapter.ViewHolder(LayoutInflater.from(context).inflate(R.layout.gallery_item, parent, false));
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {

        int column = position % 3;

        ViewGroup.MarginLayoutParams layoutParams =
                (ViewGroup.MarginLayoutParams) holder.itemView.getLayoutParams();

        if (column == 0) {
            layoutParams.setMargins(0, 0, space/2, space);
        } else if (column == 1) {
            layoutParams.setMargins(space/2, 0, space/2, space);
        } else {
            layoutParams.setMargins(space/2, 0, 0, space);
        }

        holder.itemView.setLayoutParams(layoutParams);
        holder.itemView.setOnClickListener(view -> fragment.startFullPhotosActivity(position));
        holder.image.setClipToOutline(true);
        Image.load(context, holder.image, images.get(position).getMinUrl(), true);
    }

    @Override
    public int getItemCount() { return images.size(); }

    public void insert(int position, DealImage image) {
        images.add(position, image);
        notifyItemInserted(position);
    }

    public void remove(int position) {
        images.remove(position);
        notifyItemRemoved(position);
    }

    public void updateList(List<DealImage> images) {
        this.images = images;
        notifyDataSetChanged();
    }

    public List<DealImage> getImages() { return images; }

    public class ViewHolder extends RecyclerView.ViewHolder {
        ImageView image;

        ViewHolder(View view) {
            super(view);
            image = view.findViewById(R.id.image);
        }
    }
}
